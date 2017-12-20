--TEST--
Test parametrized insert and sql_variant as an output parameter.
--DESCRIPTION--
Since output param is not supported for sql_variant columns, this test verifies a proper error message is returned
--FILE--
﻿<?php
require_once("MsCommon_mid-refactor.inc");
function testReverse($conn)
{
    $procName = getProcName('sqlReverse');
    try {
        $spCode = "CREATE PROC [$procName] @string AS SQL_VARIANT OUTPUT as SELECT @string = REVERSE(CAST(@string AS varchar(30)))";
        $conn->exec($spCode);
    } catch (Exception $e) {
        echo "Failed to create the reverse procedure\n";
        echo $e->getMessage();
    }
    try {
        $stmt = $conn->prepare("{ CALL [$procName] (?) }");
        $string = "123456789";
        $stmt->bindParam(1, $string, PDO::PARAM_STR  | PDO::PARAM_INPUT_OUTPUT, 30);
        $stmt->execute();
        // Connection with Column Encryption enabled works for non encrypted SQL_VARIANT
        // Since SQLDescribeParam is called
        if (isAEConnected() && $string === "987654321") {
            echo "Testing input output parameter with SQL_VARIANT is successful.\n";
            
        } else {
            echo "Does REVERSE work? $string \n";
        }
    } catch (Exception $e) {
        //echo "Failed when calling the reverse procedure\n";
        $error = $e->getMessage();
        if (!isAEConnected() && strpos($error, "Implicit conversion from data type sql_variant to nvarchar is not allowed.") !== false) {
            echo "Testing input output parameter with SQL_VARIANT is successful.\n";
        } else {
            echo "$error\n";
        }
    }
}
function createVariantTable($conn, $tableName)
{
    try {
        createTable($conn, $tableName, array("c1_int" => "int", "c2_variant" => "sql_variant"));
    } catch (Exception $e) {
        echo "Failed to create a test table\n";
        echo $e->getMessage();
    }
    $data = "This is to test if sql_variant works with output parameters";
    if (!isAEConnected()) {
        $tsql = "INSERT INTO [$tableName] ([c1_int], [c2_variant]) VALUES (1, ?)";
        $stmt = $conn->prepare($tsql);
        $result = $stmt->execute(array($data));
    } else {
        $tsql = "INSERT INTO [$tableName] ([c1_int], [c2_variant]) VALUES (?, ?)";
        $stmt = $conn->prepare($tsql);
        $intData = 1;
        $result = $stmt->execute(array($intData, $data));
    }
    if (! $result) {
        echo "Failed to insert data\n";
    }
}
function testOutputParam($conn, $tableName)
{
    // First, create a temporary stored procedure
    $procName = getProcName('sqlVariant');
    $spArgs = "@p1 int, @p2 sql_variant OUTPUT";
    $spCode = "SET @p2 = ( SELECT [c2_variant] FROM $tableName WHERE [c1_int] = @p1 )";
    $conn->exec("CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    $callArgs = "?, ?";
    // Data to initialize $callResult variable. This variable should be different from
    // the inserted data in the table
    $initData = "A short text";
    $callResult = $initData;
    try {
        $stmt = $conn->prepare("{ CALL [$procName] ($callArgs)}");
        $stmt->bindValue(1, 1);
        $stmt->bindParam(2, $callResult, PDO::PARAM_STR, 100);
        $stmt->execute();
        if (isAEConnected() && $callResult === "This is to test if sql_variant works with output parameters") {
            echo "Testing output parameter with SQL_VARIANT is successful.\n";
        } else {
            echo "Does SELECT from table work? $callResult \n";
        }
    } catch (Exception $e) {
        if (!strcmp($initData, $callResult)) {
            echo "initialized data and result should be the same";
        }
        $error = $e->getMessage();
        if (!isAEConnected() && strpos($error, "Operand type clash: nvarchar(max) is incompatible with sql_variant") !== false) {
            echo "Testing output parameter with SQL_VARIANT is successful.\n";
        } else {
            echo "$error\n";
        }
    }
}
try {
    // Connect
    $conn = connect();
    // Test with a simple stored procedure
    testReverse($conn);
    // Now test with another stored procedure
    $tableName = getTableName();
    createVariantTable($conn, $tableName);
    testOutputParam($conn, $tableName);
    $conn = null;
} catch (Exception $e) {
    echo $e->getMessage();
}
?>

--EXPECT--
﻿Testing input output parameter with SQL_VARIANT is successful.
Testing output parameter with SQL_VARIANT is successful.