--TEST--
Insert with query params but with various invalid inputs or boundaries
--FILE--
﻿﻿<?php
include 'tools.inc';

function ParamQueryError_MinMaxScale($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_decimal] decimal(28,4), [c3_numeric] numeric(32,4))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_decimal) VALUES (?, ?)", array(1, array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_DECIMAL(28, 34))));
    print handle_errors() . "\n";
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c3_numeric) VALUES (?, ?)", array(1, array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NUMERIC(32, -1))));
    print handle_errors() . "\n";
}

function ParamQueryError_MinMaxSize($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar_max] varchar(max))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(0))));
    print handle_errors() . "\n";
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(9000))));
    print handle_errors() . "\n";    
}

function ParamQueryError_MinMaxPrecision($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_decimal] decimal(28,4), [c3_numeric] numeric(32,4))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c3_numeric) VALUES (?, ?)", array(1, array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NUMERIC(40, 0))));
    print handle_errors() . "\n";
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_decimal) VALUES (?, ?)", array(1, array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_DECIMAL(-1, 0))));
    print handle_errors() . "\n";        
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("sqlsrv_param_query_invalid_inputs");
    try
    {
        set_time_limit(0);  
        sqlsrv_configure('WarningsReturnAsErrors', 1);  

        require_once("autonomous_setup.php");
        $database = "tempdb";
        
        // Connect
        $connectionInfo = array('Database'=>$database, 'UID'=>$username, 'PWD'=>$password, 'CharacterSet'=>'UTF-8');
        $conn = sqlsrv_connect($serverName, $connectionInfo);
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

Repro();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_param_query_invalid_inputs' test...
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.

Done
...Test 'sqlsrv_param_query_invalid_inputs' completed successfully.