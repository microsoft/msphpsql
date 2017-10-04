--TEST--
Connection Close Test
--DESCRIPTION--
Verifies that a connection can be closed multiple times and
that resources are invalidated when connection is closed.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function ConnectionClose()
{
    $testName = "Connection - Close";
    startTest($testName);

    setup();

    $noRows = 5;
    $conn1 = connect();
    $tableName = 'TC24test';
    
    createTable($conn1, $tableName);
    insertRows($conn1, $tableName, $noRows);

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
    $conn2 = connect();
    $stmt2 = selectFromTable($conn2, $tableName);
    sqlsrv_close($conn2);
    if (sqlsrv_fetch($stmt2)) {
        die("Fetch should fail when connection is closed");
    }


    $conn3 = connect();
    dropTable($conn3, $tableName);
    sqlsrv_close($conn3);

    endTest($testName);
}


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        ConnectionClose();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECTREGEX--

Warning: sqlsrv_close\(\): supplied resource is not a valid ss_sqlsrv_conn resource in .*TC24_Close.php on line 20

Warning: sqlsrv_query\(\): supplied resource is not a valid ss_sqlsrv_conn resource in .*TC24_Close.php on line 27

Warning: sqlsrv_fetch\(\): supplied resource is not a valid ss_sqlsrv_stmt resource in .*TC24_Close.php on line 36
Test "Connection - Close" completed successfully.
