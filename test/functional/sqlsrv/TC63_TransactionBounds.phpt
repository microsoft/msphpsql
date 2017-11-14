--TEST--
Transaction Boundaries Test
--DESCRIPTION--
Validates that a transaction is bound to a connection.
Test has several steps as follows:
- Two concurrent connections initiate interlaced transactions.
- Through each {connection, transaction} pair a number of rows
are inserted into the same table.
- Verifies that the table has expected number of rows.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function transaction($steps)
{
    $testName = "Transaction - Boundaries";
    startTest($testName);

    setup();
    $tableName = 'TC63test';
    $conn1 = AE\connect();
    $conn2 = AE\connect();
    AE\createTestTable($conn1, $tableName);

    $noRows1 = 2;   // inserted rows
    $noRows2 = 3;
    $noRows = 0;    // expected rows

    for ($k = 0; $k < $steps; $k++) {
        switch ($k) {
        case 0: // nested commit
            sqlsrv_begin_transaction($conn1);
            sqlsrv_begin_transaction($conn2);
            AE\insertTestRows($conn1, $tableName, $noRows1);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn2);
            sqlsrv_commit($conn1);
            $noRows += ($noRows1 + $noRows2);
            break;

        case 1: // nested rollback
            sqlsrv_begin_transaction($conn1);
            sqlsrv_begin_transaction($conn2);
            AE\insertTestRows($conn1, $tableName, $noRows1);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            sqlsrv_rollback($conn2);
            sqlsrv_rollback($conn1);
            break;

        case 2: // nested commit & rollback
            sqlsrv_begin_transaction($conn1);
            sqlsrv_begin_transaction($conn2);
            AE\insertTestRows($conn1, $tableName, $noRows1);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            sqlsrv_rollback($conn2);
            sqlsrv_commit($conn1);
            $noRows += $noRows1;
            break;

        case 3: // interleaved commit
            sqlsrv_begin_transaction($conn1);
            AE\insertTestRows($conn1, $tableName, $noRows1);
            sqlsrv_begin_transaction($conn2);
            sqlsrv_commit($conn1);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn2);
            $noRows += ($noRows1 + $noRows2);
            break;

        case 4: // interleaved insert
            sqlsrv_begin_transaction($conn1);
            AE\insertTestRows($conn1, $tableName, $noRows1);
            sqlsrv_begin_transaction($conn2);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn1);
            sqlsrv_commit($conn2);
            $noRows += ($noRows1 + $noRows2);
            break;

        case 5: // interleaved insert & commit
            sqlsrv_begin_transaction($conn1);
            sqlsrv_begin_transaction($conn2);
            AE\insertTestRows($conn1, $tableName, $noRows1);
            sqlsrv_commit($conn1);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn2);
            $noRows += ($noRows1 + $noRows2);
            break;

        case 6: // interleaved execution with commit & rollback
            sqlsrv_begin_transaction($conn1);
            AE\insertTestRows($conn1, $tableName, $noRows1);
            sqlsrv_begin_transaction($conn2);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn2);
            sqlsrv_rollback($conn1);
            $noRows += $noRows2;
            break;

        case 7: // mixed execution
            sqlsrv_begin_transaction($conn1);
            AE\insertTestRows($conn1, $tableName, $noRows1);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn1);
            $noRows += ($noRows1 + $noRows2);
            break;

        default:// no transaction
            AE\insertTestRows($conn1, $tableName, $noRows1);
            AE\insertTestRows($conn2, $tableName, $noRows2);
            $noRows += ($noRows1 + $noRows2);
            break;
        }
    }

    $rowCount = 0;
    $stmt1 = AE\selectFromTable($conn1, $tableName);
    while (sqlsrv_fetch($stmt1)) {
        $rowCount++;
    }
    sqlsrv_free_stmt($stmt1);

    trace("Row insertion attempts through all the transactions: ".$steps * ($noRows1 + $noRows2)."\n");
    trace("Rows effectively inserted through all the transactions: ".$rowCount."\n");
    if ($rowCount != $noRows) {
        die("An incorrect number of rows was fetched. Expected: ".$noRows);
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
    sqlsrv_close($conn2);

    endTest($testName);
}

try {
    transaction(9);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Transaction - Boundaries" completed successfully.
