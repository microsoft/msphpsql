--TEST--
Transaction Disconnect Test
--DESCRIPTION--
Validates that a closing a connection during a transaction will
implicitly rollback the database changes attempted by the transaction.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Transaction()
{
    include 'MsSetup.inc';

    $testName = "Transaction - Disconnect";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    CreateTable($conn1, $tableName);

    $noRows = 10;

    // Insert rows and disconnect before the transaction is commited (implicit rollback)
    Trace("\nBegin transaction...\n");
    sqlsrv_begin_transaction($conn1);
    InsertRows($conn1, $tableName, $noRows);
    Trace("Disconnect prior to commit...\n\n");
    sqlsrv_close($conn1);

    // Insert rows and commit the transaction
    $conn2 = Connect();
    Trace("Begin transaction...\n");
    sqlsrv_begin_transaction($conn2);
    $noRowsInserted = InsertRows($conn2, $tableName, $noRows);
    Trace("Transaction commit...\n");
    sqlsrv_commit($conn2);

    $rowCount = 0;
    $stmt1 = SelectFromTable($conn2, $tableName);
    while (sqlsrv_fetch($stmt1))
    {
        $rowCount++;
    }
    sqlsrv_free_stmt($stmt1);

    Trace("\nRows effectively inserted through both transactions: ".$rowCount."\n");
    if ($rowCount != $noRowsInserted)
    {
        die("An incorrect number of rows was fetched. Expected: ".$noRowsInserted);
    }

    DropTable($conn2, $tableName);  
    
    sqlsrv_close($conn2);

    EndTest($testName);
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        Transaction();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Transaction - Disconnect" completed successfully.

