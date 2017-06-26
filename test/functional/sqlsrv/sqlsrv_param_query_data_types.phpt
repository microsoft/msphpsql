--TEST--
Test insert various numeric data types and fetch them back as strings
--FILE--
﻿<?php
include 'MsCommon.inc';
include 'tools.inc';

function ParamQuery($conn, $type, $sqlsrvType, $inValue)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE [$tableName] ([col1] int, [col2] $type)");  
    sqlsrv_free_stmt($stmt);
    
    $stmt = sqlsrv_query($conn, "INSERT INTO [$tableName] VALUES (?, ?)", array(1, array($inValue, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_FLOAT, $sqlsrvType)));
    sqlsrv_free_stmt($stmt);   
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    sqlsrv_fetch($stmt);
    $value = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    
    CompareNumericData($value, $inValue);

    sqlsrv_free_stmt($stmt);       
}

function Repro()
{
    StartTest("sqlsrv_param_query_data_types");
    echo "\nTest begins...\n";
    try
    {
        set_time_limit(0);  
        sqlsrv_configure('WarningsReturnAsErrors', 1);  

        // Connect
        $conn = Connect();
        if( !$conn ) { FatalError("Could not connect.\n"); }
      
        ParamQuery($conn, "float", SQLSRV_SQLTYPE_FLOAT, 12.345);
        ParamQuery($conn, "money", SQLSRV_SQLTYPE_MONEY, 56.78);
        ParamQuery($conn, "numeric(32,4)", SQLSRV_SQLTYPE_NUMERIC(32, 4), 12.34);
        ParamQuery($conn, "real", SQLSRV_SQLTYPE_REAL, 98.760);
        ParamQuery($conn, "smallmoney", SQLSRV_SQLTYPE_SMALLMONEY, 56.78);
        
        sqlsrv_close($conn);           
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_param_query_data_types");
}

Repro();

?>
--EXPECT--
﻿
Test begins...

Done
Test "sqlsrv_param_query_data_types" completed successfully.
