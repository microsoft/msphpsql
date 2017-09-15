--TEST--
Test parametrized insert and sql_variant as an output parameter.
--DESCRIPTION--
Since output param is not supported for sql_variant columns, this test verifies a proper error message is returned  
--FILE--
﻿<?php
include 'MsCommon.inc';

function TestReverse($conn)
{
    $procName = GetTempProcName('sqlReverse');

    try
    {
        $spCode = "CREATE PROC [$procName] @string AS SQL_VARIANT OUTPUT as SELECT @string = REVERSE(CAST(@string AS varchar(30)))";

        $stmt = $conn->exec($spCode);
    }
    catch (Exception $e)
    {
        echo "Failed to create the reverse procedure\n";
        echo $e->getMessage();
    }        
    
    try
    {
        $stmt = $conn->prepare("{ CALL [$procName] (?) }");  
        $string = "123456789";
        $stmt->bindParam(1, $string, PDO::PARAM_STR, 30); 
        $stmt->execute();
        echo "Does REVERSE work? $string \n";  
    }
    catch (Exception $e)
    {
        //echo "Failed when calling the reverse procedure\n";
        echo $e->getMessage();
        echo "\n";
    }        
}

function CreateVariantTable($conn, $tableName)
{
    try 
    {
        $stmt = $conn->exec("CREATE TABLE [$tableName] ([c1_int] int, [c2_variant] sql_variant)");    
    }
    catch (Exception $e)
    {
        echo "Failed to create a test table\n";
        echo $e->getMessage();
    }        

    $tsql = "INSERT INTO [$tableName] ([c1_int], [c2_variant]) VALUES (1, ?)";    
    
    $data = "This is to test if sql_variant works with output parameters";
    
    $stmt = $conn->prepare($tsql);
    $result = $stmt->execute(array($data));
    if (! $result)
        echo "Failed to insert data\n";
}

function TestOutputParam($conn, $tableName)
{
    // First, create a temporary stored procedure
    $procName = GetTempProcName('sqlVariant');
    
    $spArgs = "@p1 int, @p2 sql_variant OUTPUT";
    $spCode = "SET @p2 = ( SELECT [c2_variant] FROM $tableName WHERE [c1_int] = @p1 )";
    
    $stmt = $conn->exec("CREATE PROC [$procName] ($spArgs) AS BEGIN $spCode END");
    $stmt = null;

    $callArgs = "?, ?";

    // Data to initialize $callResult variable. This variable should be different from
    // the inserted data in the table
    $initData = "A short text";
    $callResult = $initData;

    try
    {
        $stmt = $conn->prepare("{ CALL [$procName] ($callArgs)}");
        $stmt->bindValue(1, 1);
        $stmt->bindParam(2, $callResult, PDO::PARAM_STR, 100);
        $stmt->execute();


    }
    catch (Exception $e)
    {
        if(!strcmp($initData, $callResult))
        {
            echo "initialized data and result should be the same";
        }
        echo $e->getMessage();
        echo "\n";
    }        
}

function RunTest()
{
    StartTest("pdo_param_output_variants");
    try
    {
        include("MsSetup.inc");
        // Connect
        $conn = new PDO( "sqlsrv:server=$server;Database=$databaseName", $uid, $pwd);
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        echo "\n";

        // Test with a simple stored procedure 
        TestReverse($conn);
        
        // Now test with another stored procedure
        $tableName = GetTempTableName();   
        CreateVariantTable($conn, $tableName);

        TestOutputParam($conn, $tableName);
        
        $conn = null;
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_param_output_variants");
}

RunTest();

?>
--EXPECTREGEX--
﻿
SQLSTATE\[22018\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Operand type clash: nvarchar\(max\) is incompatible with sql_variant
SQLSTATE\[22018\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Operand type clash: nvarchar\(max\) is incompatible with sql_variant

Done
Test \"pdo_param_output_variants\" completed successfully\.
