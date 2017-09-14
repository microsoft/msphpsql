--TEST--
Test parametrized insert and sql_variant as an output parameter.
--DESCRIPTION--
sql_variant is not supported for output parameters, this test checks the error handling in this case
--FILE--
﻿<?php
include 'MsCommon.inc';

function CreateVariantTable($conn, $tableName)
{  
    $stmt = sqlsrv_query($conn, "CREATE TABLE [$tableName] ([c1_int] int, [c2_variant] sql_variant)");    
    if (! $stmt) 
        FatalError("Failed to create table.\n"); 
    
    $tsql = "INSERT INTO [$tableName] ([c1_int], [c2_variant]) VALUES (?, ?)";       
    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
    
    $data = "This is to test if sql_variant works with output parameters";

    $params = array(1, array($data, SQLSRV_PARAM_IN, $phpType));
    $stmt = sqlsrv_prepare($conn, $tsql, $params);
    sqlsrv_execute($stmt);       

    sqlsrv_free_stmt($stmt);    
}

function TestOutputParam($conn, $tableName)
{
    // First, create a temporary stored procedure
    $procName = GetTempProcName('sqlVariant');
     
    $spArgs = "@p2 sql_variant OUTPUT";
    
    $spCode = "SET @p2 = ( SELECT [c2_variant] FROM $tableName WHERE [c1_int] = 1 )";
    
    $stmt = sqlsrv_query($conn, "CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    sqlsrv_free_stmt($stmt);

    $callArgs = "?";

    // Data to initialize $callResult variable. This variable should be different from
    // the inserted data in the table
    $initData = "A short text";
    $callResult = $initData;
    
    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);

    $params = array( array( &$callResult, SQLSRV_PARAM_OUT, $phpType ));

    $stmt = sqlsrv_query($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if (! $stmt ) 
    {
        print_errors();
        if (strcmp($initData, $callResult))
        {
            echo "initialized data and results should be the same";
        }
        echo "\n";
    }
}

function TestInputAndOutputParam($conn, $tableName)
{
    $procName = GetTempProcName('sqlVariant');
    $spArgs = "@p1 int, @p2 sql_variant OUTPUT";
    $spCode = "SET @p2 = ( SELECT [c2_variant] FROM $tableName WHERE [c1_int] = @p1 )";
    $stmt = sqlsrv_query($conn, "CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    sqlsrv_free_stmt($stmt);
    
    $initData = "A short text";
    $callResult = $initData;
    $phpType = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
    
    $params = array( array( 1, SQLSRV_PARAM_IN ), array( &$callResult, SQLSRV_PARAM_OUT, $phpType ));
    $callArgs = "?, ?";
    $stmt = sqlsrv_query($conn, "{ CALL [$procName] ($callArgs)}", $params);
    if (! $stmt )
    {
        print_errors();   
        if (strcmp($initData, $callResult))
        {
            echo "initialized data and results should be the same\n";
        }
    }
}

function print_errors()
{
    $errors = sqlsrv_errors();
    $count = count($errors);

    if($count > 0)
    {
        for($i = 0; $i < $count; $i++)
        {
            print($errors[$i]['message']."\n");
        }
    }
}

function RunTest()
{
    StartTest("sqlsrv_param_output_variants");
    try
    {
        Setup();

        // Connect
        $conn = Connect();

        // Create a temp table that will be automatically dropped once the connection is closed
        $tableName = GetTempTableName();
        CreateVariantTable($conn, $tableName);
        echo "\n";
        
        TestOutputParam($conn, $tableName);
        TestInputAndOutputParam($conn, $tableName);
        
        sqlsrv_close($conn);   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_param_output_variants");
}

RunTest();

?>
--EXPECTREGEX--
 ﻿
\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Operand type clash: varchar\(max\) is incompatible with sql_variant

\[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Operand type clash: varchar\(max\) is incompatible with sql_variant

Done
Test \"sqlsrv_param_output_variants\" completed successfully\.