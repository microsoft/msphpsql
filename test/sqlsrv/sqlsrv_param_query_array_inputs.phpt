--TEST--
Test insert various numeric data types and fetch them back as strings
--FILE--
﻿<?php
include 'MsCommon.inc';

function ExecData_Value($conn, $numRows, $phpType = SQLSRV_PHPTYPE_NULL)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE [$tableName] ([c1_int] int, [c2_smallint] smallint)");  
    sqlsrv_free_stmt($stmt);
    
    if ($phpType == SQLSRV_PHPTYPE_NULL) 
    {
        echo "Insert integers without PHP type\n";
        $stmt = sqlsrv_prepare($conn, "INSERT INTO [$tableName] (c1_int, c2_smallint) VALUES (?, ?)", array(array(&$v1), array(&$v2)));
    }
    else // SQLSRV_PHPTYPE_INT
    {
        echo "Insert integers as SQLSRV_PHPTYPE_INT\n";
        $stmt = sqlsrv_prepare($conn, "INSERT INTO [$tableName] (c1_int, c2_smallint) VALUES (?, ?)", array(array(&$v1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT), array(&$v2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT)));
    }
    
    $value = 1;    
    for ($i = 0; $i < $numRows; $i++)
    {
        $v1 = $value;
        $v2 = $v1 + 1;
        sqlsrv_execute($stmt);
        
        $value += 10;
    }
    
    sqlsrv_free_stmt($stmt);   
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    FetchData($stmt, $numRows);

    sqlsrv_free_stmt($stmt);       
}

function ExecData_Param($conn, $numRows, $withParam = false)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE [$tableName] ([c1_float] float, [c2_real] real)");  
    sqlsrv_free_stmt($stmt);
    
    if ($withParam) 
    {
        echo "Insert floats with direction specified\n";
        $stmt = sqlsrv_prepare($conn, "INSERT INTO [$tableName] (c1_float, c2_real) VALUES (?, ?)", array(array(&$v1, SQLSRV_PARAM_IN), array(&$v2, SQLSRV_PARAM_IN)));
    }
    else // no param
    {
        echo "Insert floats without direction\n";
        $stmt = sqlsrv_prepare($conn, "INSERT INTO [$tableName] (c1_float, c2_real) VALUES (?, ?)", array(&$v1, &$v2));
    }
    
    $value = 1.0;    
    for ($i = 0; $i < $numRows; $i++)
    {
        $v1 = $value;
        $v2 = $v1 + 1.0;
        sqlsrv_execute($stmt);
        
        $value += 10;
    }
    
    sqlsrv_free_stmt($stmt);   
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    FetchData($stmt, $numRows);

    sqlsrv_free_stmt($stmt);           
}

function FetchData($stmt, $numRows)
{
    for ($i = 0; $i < $numRows; $i++)
    {
        sqlsrv_fetch($stmt);
        
        $value = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$value, ";

        $value = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$value\n";       
    }    
}

function Repro()
{
    StartTest("sqlsrv_param_query_array_inputs");
    echo "\nTest begins...\n";
    try
    {
        set_time_limit(0);  
        sqlsrv_configure('WarningsReturnAsErrors', 1);  
     
        // Connect
        $conn = Connect();
        if( !$conn ) { FatalError("Could not connect.\n"); }
      
        $numRows = 5;

        ExecData_Value($conn, $numRows);
        ExecData_Value($conn, $numRows, SQLSRV_PHPTYPE_INT);
        ExecData_Param($conn, $numRows, true);
        ExecData_Param($conn, $numRows);
        
        sqlsrv_close($conn);           
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_param_query_array_inputs");
}

Repro();

?>
--EXPECT--
﻿
Test begins...
Insert integers without PHP type
1, 2
11, 12
21, 22
31, 32
41, 42
Insert integers as SQLSRV_PHPTYPE_INT
1, 2
11, 12
21, 22
31, 32
41, 42
Insert floats with direction specified
1.0, 2.0
11.0, 12.0
21.0, 22.0
31.0, 32.0
41.0, 42.0
Insert floats without direction
1.0, 2.0
11.0, 12.0
21.0, 22.0
31.0, 32.0
41.0, 42.0

Done
Test "sqlsrv_param_query_array_inputs" completed successfully.
