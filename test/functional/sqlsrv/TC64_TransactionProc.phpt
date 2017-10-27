--TEST--
Transaction with Stored Procedure Test
--DESCRIPTION--
Verifies the basic transaction behavior in the context of an
INSERT query performed within a stored procedure.
Two types of sequences are explored.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function transaction($minType, $maxType)
{
    $testName = "Transaction - Stored Proc";
    startTest($testName);

    setup();
    $tableName = 'TC64test';
    $procName = "TC64test_proc";
    $conn1 = AE\connect();

    $colName = "c1";
    $dataSize = 512;
    for ($k = $minType; $k <= $maxType; $k++) {
        switch ($k) {
            case 20:    // binary
            case 21:    // varbinary
            case 22:    // varbinary(max)
                $data = null;
                break;
            default:
                $data = getSampleData($k);
                break;
        }
        if ($data != null) {
            $sqlType = getSqlType($k);
            $driverType = getSqlsrvSqlType($k, $dataSize);

            if ($k == 10 || $k == 11) {
                // do not encrypt money type -- ODBC restrictions
                $noEncrypt = true;
            } else {
                $noEncrypt = false;
            }
            $columns = array(new AE\ColumnMeta($sqlType, $colName, null, true, $noEncrypt));
            
            AE\createTable($conn1, $tableName, $columns);
            createTransactionProc($conn1, $tableName, $colName, $procName, $sqlType);

            $noRows = execTransactionProc($conn1, $procName, $data, $driverType, true);
            if ($noRows != 1) {
                die("$sqlType: Incorrect row count after commit: $noRows");
            }
            $noRows = execTransactionProc($conn1, $procName, $data, $driverType, false);
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

function createTransactionProc($conn, $tableName, $colName, $procName, $sqlType)
{
    $procArgs = "@p1 $sqlType, @p2 INT OUTPUT";
    $procCode = "SET NOCOUNT ON; INSERT INTO [$tableName] ($colName) VALUES (@p1) SET @p2 = (SELECT COUNT(*) FROM [$tableName])";
    createProc($conn, $procName, $procArgs, $procCode);
}

function execTransactionProc($conn, $procName, $data, $driverType, $commitMode)
{
    // Always Encrypted feature requires SQL Types to be specified for sqlsrv_query
    // https://github.com/Microsoft/msphpsql/wiki/Features#aelimitation
    if (AE\isColEncrypted()) {
        $inType = $driverType;
    } else {
        $inType = null;
    }
    $retValue = -1;
    $callArgs =  array(array($data, SQLSRV_PARAM_IN, null, $inType), 
                       array(&$retValue, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT));
    sqlsrv_begin_transaction($conn);
    $stmt = callProc($conn, $procName, "?, ?", $callArgs);
    if ($commitMode === true) {   // commit
        sqlsrv_commit($conn);
    } else {   // rollback
        sqlsrv_rollback($conn);
    }
    return ($retValue);
}

try {
    transaction(1, 28);
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Transaction - Stored Proc" completed successfully.
