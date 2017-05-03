--TEST--
Transaction with Stored Procedure Test
--DESCRIPTION--
Verifies the basic transaction behavior in the context of an
INSERT query performed within a stored procedure.
Two types of sequences are explored.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Transaction($minType, $maxType)
{
    include 'MsSetup.inc';

    $testName = "Transaction - Stored Proc";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    $colName = "c1";
    for ($k = $minType; $k <= $maxType; $k++)
    {
        switch ($k)
        {
            case 20:    // binary
            case 21:    // varbinary
            case 22:    // varbinary(max)
                $data = null;
                break;
            default:
                $data = GetSampleData($k);
                break;
        }
        if ($data != null)
        {
            $sqlType = GetSqlType($k);

            CreateTableEx($conn1, $tableName, "[$colName] $sqlType");
            CreateTransactionProc($conn1, $tableName, $colName, $procName, $sqlType);

            $noRows = ExecTransactionProc($conn1, $procName, $data, true);
            if ($noRows != 1)
            {
                die("$sqlType: Incorrect row count after commit: $noRows");
            }
            $noRows = ExecTransactionProc($conn1, $procName, $data, false);
            if ($noRows != 2)
            {
                die("$sqlType: Incorrect row count after rollback: $noRows");
            }
            $noRows = NumRows($conn1, $tableName);
            if ($noRows != 1)
            {
                die("$sqlType: Incorrect total row count: $noRows");
            }


            DropProc($conn1, $procName);
            DropTable($conn1, $tableName);
        }
    }   

    
    sqlsrv_close($conn1);

    EndTest($testName);
}

function CreateTransactionProc($conn, $tableName, $colName, $procName, $sqlType)
{
    $procArgs = "@p1 $sqlType, @p2 INT OUTPUT";
        $procCode = "SET NOCOUNT ON; INSERT INTO [$tableName] ($colName) VALUES (@p1) SET @p2 = (SELECT COUNT(*) FROM [$tableName])";
    CreateProc($conn, $procName, $procArgs, $procCode);
}

function ExecTransactionProc($conn, $procName, $data, $commitMode)
{
    $retValue = -1;
    $callArgs =  array(array($data, SQLSRV_PARAM_IN), array(&$retValue, SQLSRV_PARAM_OUT));

    sqlsrv_begin_transaction($conn);
    $stmt = CallProc($conn, $procName, "?, ?", $callArgs);
    if ($commitMode === true)
    {   // commit
        sqlsrv_commit($conn);
    }
    else
    {   // rollback
        sqlsrv_rollback($conn);
    }

    return ($retValue);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        Transaction(1, 28);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Transaction - Stored Proc" completed successfully.

