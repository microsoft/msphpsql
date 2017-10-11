--TEST--
Statement Cancel Test
--DESCRIPTION--
Verifies that "sqlsrv_cancel" discards any pending data in current result set
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function cancel()
{
    $testName = "Statement - Cancel";
    startTest($testName);

    setup();
    $conn1 = connect();

    $tableName = 'TC35test';
    createTable($conn1, $tableName);
    insertRows($conn1, $tableName, 5);

    trace("Executing SELECT query on $tableName ...");
    $stmt1 = selectFromTable($conn1, $tableName);
    if (sqlsrv_fetch($stmt1) === false) {
        fatalError("Failed to retrieve data from test table");
    }
    trace(" data fetched successfully.\n");

    trace("Cancel statement and attempt another fetch (expected to fail) ...\n");
    sqlsrv_cancel($stmt1);
    if (sqlsrv_fetch($stmt1) === false) {
        handleErrors();
    } else {
        die("No succesfull data fetch expectd after statement cancel");
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

try {
    cancel();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Statement - Cancel" completed successfully.
