--TEST--
Temporary table
--SKIPIF--
--FILE--
<?php

    require_once("MsCommon.inc");

    // connect
    $conn = connect();
    if (!$conn) {
        fatalError("Connection could not be established.\n");
    }

    // Create temporary table and insert data
    $sql = "CREATE TABLE #T (col VARCHAR(32));
            INSERT INTO #T VALUES ('PHP7 SQLSRV')";
    $stmt = sqlsrv_query($conn, $sql);

    // Get the data
    $sql = "SELECT * FROM #T";
    $stmt = sqlsrv_query($conn, $sql);
    sqlsrv_fetch($stmt);
    var_dump(sqlsrv_get_field($stmt, 0));

    // Free statement and close connection
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    print "Done"
?>

--EXPECT--
string(11) "PHP7 SQLSRV"
Done
