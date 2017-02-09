--TEST--
sqlsrv_fetch() with SQLSRV_SCROLL_ABSOLUTE using out of range offset
--SKIPIF--
--FILE--
<?php

function print_errors($message = "")
{
    if (strlen($message) > 0)
    {
        echo $message . "\n";
    }
    die( print_r( sqlsrv_errors(), true));
}

function test()
{
    require_once("autonomous_setup.php");

    // Connect
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if( !$conn ) { print_errors(); }

    // Prepare the statement
    $sql = "select name from sys.databases";
    $stmt = sqlsrv_prepare( $conn, $sql, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED) );
    if( $stmt === false ) { print_errors(); }
    sqlsrv_execute($stmt);

    // Get row count
    $row_count = sqlsrv_num_rows( $stmt );  
    if ($row_count == 0) { print_errors("There should be at least one row!\n"); }

    sqlsrv_execute($stmt);
    $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);  
    $field = sqlsrv_get_field($stmt, 0);
    if (! $field) { print_errors(); }

    $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, 3);  
    $field = sqlsrv_get_field($stmt, 0);
    if (! $field) { print_errors(); }

    // this should return false
    $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, $row_count);  
    if ($row) { print_errors("This should return false!"); }
    $field = sqlsrv_get_field($stmt, 0);
    if ($field !== false) { print_errors("This should have resulted in error!"); }

    sqlsrv_free_stmt( $stmt);
    sqlsrv_close($conn);
}

test();

print "Done";
?>

--EXPECT--
Done
