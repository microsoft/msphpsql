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
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Transaction($steps)
{
    include 'MsSetup.inc';

    $testName = "Transaction - Boundaries";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    $conn2 = Connect();
    CreateTable($conn1, $tableName);

    $noRows1 = 2;   // inserted rows
    $noRows2 = 3;
    $noRows = 0;    // expected rows

    for ($k = 0; $k < $steps; $k++)
    {
        switch ($k)
        {
        case 0: // nested commit
            sqlsrv_begin_transaction($conn1);
            sqlsrv_begin_transaction($conn2);
            InsertRows($conn1, $tableName, $noRows1);
            InsertRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn2);
            sqlsrv_commit($conn1);
            $noRows += ($noRows1 + $noRows2);
            break;

        case 1: // nested rollback
            sqlsrv_begin_transaction($conn1);
            sqlsrv_begin_transaction($conn2);
            InsertRows($conn1, $tableName, $noRows1);
            InsertRows($conn2, $tableName, $noRows2);
            sqlsrv_rollback($conn2);
            sqlsrv_rollback($conn1);
            break;

        case 2: // nested commit & rollback
            sqlsrv_begin_transaction($conn1);
            sqlsrv_begin_transaction($conn2);
            InsertRows($conn1, $tableName, $noRows1);
            InsertRows($conn2, $tableName, $noRows2);
            sqlsrv_rollback($conn2);
            sqlsrv_commit($conn1);
            $noRows += $noRows1;
            break;

        case 3: // interleaved commit
            sqlsrv_begin_transaction($conn1);
            InsertRows($conn1, $tableName, $noRows1);
            sqlsrv_begin_transaction($conn2);
            sqlsrv_commit($conn1);
            InsertRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn2);
            $noRows += ($noRows1 + $noRows2);
            break;

        case 4: // interleaved insert
            sqlsrv_begin_transaction($conn1);
            InsertRows($conn1, $tableName, $noRows1);
            sqlsrv_begin_transaction($conn2);
            InsertRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn1);
            sqlsrv_commit($conn2);
            $noRows += ($noRows1 + $noRows2);
            break;

        case 5: // interleaved insert & commit
            sqlsrv_begin_transaction($conn1);
            sqlsrv_begin_transaction($conn2);
            InsertRows($conn1, $tableName, $noRows1);
            sqlsrv_commit($conn1);
            InsertRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn2);
            $noRows += ($noRows1 + $noRows2);
            break;

        case 6: // interleaved execution with commit & rollback
            sqlsrv_begin_transaction($conn1);
            InsertRows($conn1, $tableName, $noRows1);
            sqlsrv_begin_transaction($conn2);
            InsertRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn2);
            sqlsrv_rollback($conn1);
            $noRows += $noRows2;
            break;

        case 7: // mixed execution
            sqlsrv_begin_transaction($conn1);
            InsertRows($conn1, $tableName, $noRows1);
            InsertRows($conn2, $tableName, $noRows2);
            sqlsrv_commit($conn1);
            $noRows += ($noRows1 + $noRows2);
            break;

        default:// no transaction
            InsertRows($conn1, $tableName, $noRows1);
            InsertRows($conn2, $tableName, $noRows2);
            $noRows += ($noRows1 + $noRows2);
            break;
        }
    }

    $rowCount = 0;
    $stmt1 = SelectFromTable($conn1, $tableName);
    while (sqlsrv_fetch($stmt1))
    {
        $rowCount++;
    }
    sqlsrv_free_stmt($stmt1);

    Trace("Row insertion attempts through all the transactions: ".$steps * ($noRows1 + $noRows2)."\n");
    Trace("Rows effectively inserted through all the transactions: ".$rowCount."\n");
    if ($rowCount != $noRows)
    {
        die("An incorrect number of rows was fetched. Expected: ".$noRows);
    }

    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);
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
        Transaction(9);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Transaction - Boundaries" completed successfully.
