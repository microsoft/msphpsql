--TEST--
Transaction Execution Test
--DESCRIPTION--
Verifies the basic transaction behavior in the context of INSERT queries.
Two types of sequences are explored:
    Begin -> Commit
    Begin -> Rollback
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function transaction()
{
    $testName = "Transaction - Execution";
    startTest($testName);

    setup();
    $tableName = 'TC61test';
    $conn1 = AE\connect();
    AE\createTestTable($conn1, $tableName);

    $noRows = 10;
    execTransaction($conn1, false, $tableName, $noRows);    // rollback
    execTransaction($conn1, true, $tableName, $noRows); // submit

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function execTransaction($conn, $mode, $tableName, $noRows)
{
    if ($mode === true) {
        trace("\nSUBMIT sequence:\n\t");
    } else {
        trace("\nROLLBACK sequence:\n\t");
    }
    sqlsrv_begin_transaction($conn);
    $noRowsInserted = AE\insertTestRows($conn, $tableName, $noRows);
    if ($mode === true) {
        trace("\tTransaction submit...");
        sqlsrv_commit($conn);
    } else {
        trace("\tTransaction rollback...");
        sqlsrv_rollback($conn);
    }

    $rowCount = 0;
    $stmt = AE\selectFromTable($conn, $tableName);
    while (sqlsrv_fetch($stmt)) {
        $rowCount++;
    }
    sqlsrv_free_stmt($stmt);

    trace(" rows effectively inserted: $rowCount.\n");
    if ($mode === true) {   // commit: expected to fetch all inserted rows
        if ($rowCount != $noRowsInserted) {
            die("An incorrect number of rows was fetched. Expected: ".$noRows);
        }
    } else {   // rollback: no row should have been inserted
        if ($rowCount > 0) {
            die("No row should have been fetched after rollback");
        }
    }
}

try {
    transaction();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Transaction - Execution" completed successfully.
