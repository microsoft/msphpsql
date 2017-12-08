--TEST--
Test parametrized insert and sql_variant as an output parameter.
--DESCRIPTION--
Normally, sql_variant is not supported for output parameters, this test checks the error handling in this case. However, when Always Encrypted is enabled, we are able to bind output parameters with prepared
statements.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿<?php
require_once('MsCommon.inc');

function createVariantTable($conn, $tableName)
{
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('sql_variant', 'c2_variant'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table.\n");
    }

    $tsql = "INSERT INTO [$tableName] ([c1_int], [c2_variant]) VALUES (?, ?)";
    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);

    $data = "This is to test if sql_variant works with output parameters";

    $params = array(1, array($data, SQLSRV_PARAM_IN, $phpType));
    $stmt = sqlsrv_prepare($conn, $tsql, $params);
    sqlsrv_execute($stmt);

    sqlsrv_free_stmt($stmt);
}

function execProcedure($conn, $tsql, $params)
{
    if (AE\isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, $tsql, $params);
        if ($stmt) {
            sqlsrv_execute($stmt);
        }
    } else {
        $stmt = sqlsrv_query($conn, $tsql, $params);
    }
    return $stmt;
}

function testOutputParam($conn, $tableName)
{
    // First, create a stored procedure
    $procName = 'sqlVariant_out_proc';

    $spArgs = "@p2 sql_variant OUTPUT";

    // There is only one row in the table
    $spCode = "SET @p2 = ( SELECT [c2_variant] FROM $tableName )";

    $stmt = sqlsrv_query($conn, "CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    sqlsrv_free_stmt($stmt);

    $callArgs = "?";

    // Data to initialize $callResult variable. This variable should be different from
    // the inserted data in the table
    $initData = "A short text";
    $callResult = $initData;

    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);

    $params = array(array(&$callResult, SQLSRV_PARAM_OUT, $phpType));

    $stmt = execProcedure($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if (!$stmt) {
        verifyErrorMessage(sqlsrv_errors()[0]);
    }
    
    dropProc($conn, $procName);
}

function testInputAndOutputParam($conn, $tableName)
{
    $procName = 'sqlVariant_inout_proc';
    $spArgs = "@p1 int, @p2 sql_variant OUTPUT";
    $spCode = "SET @p2 = ( SELECT [c2_variant] FROM $tableName WHERE [c1_int] = @p1)";
    $stmt = sqlsrv_query($conn, "CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    sqlsrv_free_stmt($stmt);

    $initData = "A short text";
    $callResult = $initData;
    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);

    $params = array(array(1, SQLSRV_PARAM_IN), array(&$callResult, SQLSRV_PARAM_OUT, $phpType));
    $callArgs = "?, ?";
    $stmt = execProcedure($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if (!$stmt) {
        verifyErrorMessage(sqlsrv_errors()[0]);
    }

    dropProc($conn, $procName);
}

function verifyErrorMessage($error)
{
    if (AE\isColEncrypted()) {
        fatalError("With AE this should not have failed!");
    }
    
    // Expect to fail only when AE is disabled
    $expected = "Operand type clash: varchar(max) is incompatible with sql_variant";
    if (strpos($error['message'], $expected) === false) {
        echo $error['message'] . PHP_EOL;
        fatalError("Expected error: $expected\n");
    }
}

setup();

// connect
$conn = AE\connect();

// Create a test table 
$tableName = 'test_output_variants';
createVariantTable($conn, $tableName);

testOutputParam($conn, $tableName);
testInputAndOutputParam($conn, $tableName);

dropTable($conn, $tableName);

sqlsrv_close($conn);
print "Test completed successfully\n";
?>
--EXPECT--
﻿Test completed successfully