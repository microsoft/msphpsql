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
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Transaction()
{
    include 'MsSetup.inc';

    $testName = "Transaction - Execution";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    CreateTable($conn1, $tableName);

    $noRows = 10;
    ExecTransaction($conn1, false, $tableName, $noRows);    // rollback
    ExecTransaction($conn1, true, $tableName, $noRows); // submit

    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);

    EndTest($testName);
}


function ExecTransaction($conn, $mode, $tableName, $noRows)
{
    if ($mode === true)
    {
        Trace("\nSUBMIT sequence:\n\t");
    }
    else
    {
        Trace("\nROLLBACK sequence:\n\t");
    }
    sqlsrv_begin_transaction($conn);
    $noRowsInserted = InsertRows($conn, $tableName, $noRows);
    if ($mode === true)
    {
        Trace("\tTransaction submit...");
        sqlsrv_commit($conn);
    }
    else
    {
        Trace("\tTransaction rollback...");
        sqlsrv_rollback($conn);
    }

    $rowCount = 0;
    $stmt = SelectFromTable($conn, $tableName);
    while (sqlsrv_fetch($stmt))
    {
        $rowCount++;
    }
    sqlsrv_free_stmt($stmt);

    Trace(" rows effectively inserted: $rowCount.\n");
    if ($mode === true)
    {   // commit: expected to fetch all inserted rows
        if ($rowCount != $noRowsInserted)
        {
            die("An incorrect number of rows was fetched. Expected: ".$noRows);
        }
    }
    else
    {   // rollback: no row should have been inserted
        if ($rowCount > 0)
        {
            die("No row should have been fetched after rollback");
        }
    }
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
Test "Transaction - Execution" completed successfully.

