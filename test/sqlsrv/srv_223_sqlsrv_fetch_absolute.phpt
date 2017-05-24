--TEST--
sqlsrv_fetch() with SQLSRV_SCROLL_ABSOLUTE using out of range offset
--SKIPIF--
--FILE--
<?php

function test()
{
    require_once("MsCommon.inc");

    // Connect
    $conn = Connect();
    if( !$conn ) {
        PrintErrors("Connection could not be established.\n");
    }

    // Prepare the statement
    $sql = "select * from cd_info";
    $stmt = sqlsrv_prepare( $conn, $sql, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED) );
    if( $stmt === false ) { PrintErrors(); }
    sqlsrv_execute($stmt);

    // Get row count
    $row_count = sqlsrv_num_rows( $stmt );  
    if ($row_count == 0) { PrintErrors("There should be at least one row!\n"); }

    sqlsrv_execute($stmt);
    $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);  
    $field = sqlsrv_get_field($stmt, 0);
    if (! $field) { PrintErrors(); }

    $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_LAST);  
    $field = sqlsrv_get_field($stmt, 0);
    if (! $field) { PrintErrors(); }

    // this should return false
    $row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, $row_count);  
    if ($row) { PrintErrors("This should return false!"); }
    $field = sqlsrv_get_field($stmt, 0);
    if ($field !== false) { PrintErrors("This should have resulted in error!"); }

    sqlsrv_free_stmt( $stmt);
    sqlsrv_close($conn);
}

test();

print "Done";
?>

--EXPECT--
Done
