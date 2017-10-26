--TEST--
Connection Close Test
--DESCRIPTION--
Verifies that a connection can be closed multiple times and
that resources are invalidated when connection is closed.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function connectionClose()
{
    $testName = "Connection - Close";
    startTest($testName);
    setup();
    $conn1 = AE\connect();
    $tableName = 'TC24test';  
    
    // Insert some random rows
    AE\createTestTable($conn1, $tableName);
    AE\insertTestRows($conn1, $tableName, 5);

    // Close connection twice
    for ($i = 0; $i < 2; $i++) {
        $ret = sqlsrv_close($conn1);
        if ($ret === false) {
            die("Unexpected return for sqlsrv_close: $ret");
        }
    }

    // Invalid Query
    $stmt1 = sqlsrv_query($conn1, "SELECT * FROM [$tableName]");
    if ($stmt1) {
        die("Select query should fail when connection is closed");
    }

    // Invalid Statement
    $conn2 = AE\connect();
    $stmt2 = AE\selectFromTable($conn2, $tableName);
    sqlsrv_close($conn2);
    if (sqlsrv_fetch($stmt2)) {
        die("Fetch should fail when connection is closed");
    }

    $conn3 = AE\connect();
    dropTable($conn3, $tableName); 
    sqlsrv_close($conn3);    

    endTest($testName);
}

try {
    connectionClose();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECTREGEX--

Warning: sqlsrv_close\(\): supplied resource is not a valid ss_sqlsrv_conn resource in .*TC24_Close.php on line 18

Warning: sqlsrv_query\(\): supplied resource is not a valid ss_sqlsrv_conn resource in .*TC24_Close.php on line 25

Warning: sqlsrv_fetch\(\): supplied resource is not a valid ss_sqlsrv_stmt resource in .*TC24_Close.php on line 34
Test "Connection - Close" completed successfully.
