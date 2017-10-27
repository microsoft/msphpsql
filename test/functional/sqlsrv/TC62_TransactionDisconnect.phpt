--TEST--
Transaction Disconnect Test
--DESCRIPTION--
Validates that a closing a connection during a transaction will
implicitly rollback the database changes attempted by the transaction.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function transaction()
{
    $testName = "Transaction - Disconnect";
    startTest($testName);

    setup();
    $tableName = 'TC62test';
    $conn1 = AE\connect();
    AE\createTestTable($conn1, $tableName);

    $noRows = 10;
    // Insert rows and disconnect before the transaction is commited (implicit rollback)
    trace("\nBegin transaction...\n");
    sqlsrv_begin_transaction($conn1);
    AE\insertTestRows($conn1, $tableName, $noRows);
    trace("Disconnect prior to commit...\n\n");
    sqlsrv_close($conn1);

    // Insert rows and commit the transaction
    $conn2 = AE\connect();
    trace("Begin transaction...\n");
    sqlsrv_begin_transaction($conn2);
    $noRowsInserted = AE\insertTestRows($conn2, $tableName, $noRows);
    trace("Transaction commit...\n");
    sqlsrv_commit($conn2);

    $rowCount = 0;
    $stmt1 = AE\selectFromTable($conn2, $tableName);
    while (sqlsrv_fetch($stmt1)) {
        $rowCount++;
    }
    sqlsrv_free_stmt($stmt1);

    trace("\nRows effectively inserted through both transactions: ".$rowCount."\n");
    if ($rowCount != $noRowsInserted) {
        die("An incorrect number of rows was fetched. Expected: ".$noRowsInserted);
    }

    dropTable($conn2, $tableName);

    sqlsrv_close($conn2);

    endTest($testName);
}

try {
    transaction();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Transaction - Disconnect" completed successfully.
