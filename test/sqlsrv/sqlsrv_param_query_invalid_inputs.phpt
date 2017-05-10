--TEST--
Insert with query params but with various invalid inputs or boundaries
--FILE--
﻿﻿<?php
include 'MsCommon.inc';

function ParamQueryError_MinMaxScale($conn)
{
    $tableName = GetTempTableName('MinMaxScale');
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_decimal] decimal(28,4), [c3_numeric] numeric(32,4))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_decimal) VALUES (?, ?)", array(1, array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_DECIMAL(28, 34))));
    PrintErrors();
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c3_numeric) VALUES (?, ?)", array(1, array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NUMERIC(32, -1))));
    PrintErrors();
}

function ParamQueryError_MinMaxSize($conn)
{
    $tableName = GetTempTableName('MinMaxSize');
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar_max] varchar(max))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(0))));
    PrintErrors();
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(9000))));
    PrintErrors();    
}

function ParamQueryError_MinMaxPrecision($conn)
{
    $tableName = GetTempTableName('MinMaxPrecision');
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_decimal] decimal(28,4), [c3_numeric] numeric(32,4))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c3_numeric) VALUES (?, ?)", array(1, array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NUMERIC(40, 0))));
    PrintErrors();
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_decimal) VALUES (?, ?)", array(1, array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_DECIMAL(-1, 0))));
    PrintErrors();        
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    StartTest("sqlsrv_param_query_invalid_inputs");
    echo "\nTest begins...\n";
    try
    {
        set_time_limit(0);  
        sqlsrv_configure('WarningsReturnAsErrors', 1);  

        // Connect
        $conn = Connect(); 
        if( !$conn ) { FatalError("Could not connect.\n"); }
                     
        ParamQueryError_MinMaxScale($conn);
        ParamQueryError_MinMaxSize($conn);
        ParamQueryError_MinMaxPrecision($conn);

        sqlsrv_close($conn);                   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_param_query_invalid_inputs");
}

RunTest();

?>
--EXPECT--
﻿﻿
Test begins...
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.

Done
Test "sqlsrv_param_query_invalid_inputs" completed successfully.