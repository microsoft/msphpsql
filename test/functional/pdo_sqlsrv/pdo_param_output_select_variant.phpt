--TEST--
Test sql_variant as an output parameter 
--DESCRIPTION--
Since output param is not supported for sql_variant columns, this test verifies a proper error message is returned  
--FILE--
﻿<?php
include 'MsCommon.inc';

function TestSimpleSelect($conn, $tableName)
{
    $count = 0;  

    $stmt = $conn->prepare("SELECT ? = COUNT(* ) FROM $tableName");  
    $stmt->bindParam( 1, $count, PDO::PARAM_INT, 4 );  
    $stmt->execute();  
    echo "Number of rows: $count\n";  

    $value = 'xx';
    
    $stmt = $conn->prepare("SELECT ? = c2_variant FROM $tableName");  
    $stmt->bindParam( 1, $value, PDO::PARAM_STR, 50 );  
    $stmt->execute();  
    echo "Variant column: $value\n\n";  
    
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

function RunTest()
{
    StartTest("pdo_param_output_select_variant");
    try
    {
        include("MsSetup.inc");
        // Connect
        $conn = new PDO( "sqlsrv:server=$server;Database=$databaseName", $uid, $pwd);
        $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
        echo "\n";

        // Now test with another stored procedure
        $tableName = GetTempTableName();   
        CreateVariantTable($conn, $tableName);

        // Test a simple select to get output
        TestSimpleSelect($conn, $tableName);
        
        $conn = null;
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("pdo_param_output_select_variant");
}

RunTest();

?>
--EXPECTREGEX--
﻿
Number of rows: 1
SQLSTATE\[42000\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Implicit conversion from data type sql_variant to nvarchar\(max\) is not allowed. Use the CONVERT function to run this query.
Done
Test \"pdo_param_output_select_variant\" completed successfully\.
