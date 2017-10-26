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
require_once('MsCommon.inc');

function Transaction($minType, $maxType)
{
    include 'MsSetup.inc';

    $testName = "Transaction - Stored Proc";
    startTest($testName);

    setup();
    $conn1 = connect();

    $colName = "c1";
    for ($k = $minType; $k <= $maxType; $k++) {
        switch ($k) {
            case 20:    // binary
            case 21:    // varbinary
            case 22:    // varbinary(max)
                $data = null;
                break;
            default:
                $data = GetSampleData($k);
                break;
        }
        if ($data != null) {
            $sqlType = GetSqlType($k);

            createTableEx($conn1, $tableName, "[$colName] $sqlType");
            CreateTransactionProc($conn1, $tableName, $colName, $procName, $sqlType);

            $noRows = ExecTransactionProc($conn1, $procName, $data, true);
            if ($noRows != 1) {
                die("$sqlType: Incorrect row count after commit: $noRows");
            }
            $noRows = ExecTransactionProc($conn1, $procName, $data, false);
            if ($noRows != 2) {
                die("$sqlType: Incorrect row count after rollback: $noRows");
            }
            $noRows = numRows($conn1, $tableName);
            if ($noRows != 1) {
                die("$sqlType: Incorrect total row count: $noRows");
            }


            dropProc($conn1, $procName);
            dropTable($conn1, $tableName);
        }
    }


    sqlsrv_close($conn1);

    endTest($testName);
}

function CreateTransactionProc($conn, $tableName, $colName, $procName, $sqlType)
{
    $procArgs = "@p1 $sqlType, @p2 INT OUTPUT";
    $procCode = "SET NOCOUNT ON; INSERT INTO [$tableName] ($colName) VALUES (@p1) SET @p2 = (SELECT COUNT(*) FROM [$tableName])";
    createProc($conn, $procName, $procArgs, $procCode);
}

function ExecTransactionProc($conn, $procName, $data, $commitMode)
{
    $retValue = -1;
    $callArgs =  array(array($data, SQLSRV_PARAM_IN), array(&$retValue, SQLSRV_PARAM_OUT));

    sqlsrv_begin_transaction($conn);
    $stmt = callProc($conn, $procName, "?, ?", $callArgs);
    if ($commitMode === true) {   // commit
        sqlsrv_commit($conn);
    } else {   // rollback
        sqlsrv_rollback($conn);
    }

    return ($retValue);
}


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        Transaction(1, 28);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECT--
Test "Transaction - Stored Proc" completed successfully.
