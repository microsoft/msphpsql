--TEST--
Insert with query params but with wrong parameters or types
--FILE--
﻿﻿<?php
include 'tools.inc';

function ParamQueryError_PhpType_Mismatch($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar_max] varchar(max))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array(1.5, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_FLOAT, SQLSRV_SQLTYPE_VARCHAR('max'))));
    sqlsrv_free_stmt($stmt);
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    $result = sqlsrv_fetch($stmt);
    if (! $result)
    {
        echo "Fetch should succeed\n";
    }
    $value0 = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    $value1 = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($value0 != "1" || $value1 != "1.5")
    {
        echo "Data $value0 or $value1 unexpected\n";
    }
}

function ParamQueryError_Dir_Invalid($conn)
{
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar_max] varchar(max))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", 32, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR('max'))));
        
    print handle_errors() . "\n";
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", 'SQLSRV_PARAM_INTERNAL', SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR('max'))));

    print handle_errors() . "\n";
}

function ParamQueryError_PhpType_Encoding($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar_max] varchar(max))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('SQLSRV_ENC_UNKNOWN'), null)));
    print handle_errors() . "\n";    
}

function ParamQueryError_PhpType_Invalid($conn)
{
    $tableName = GetTempTableName();
    
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar_max] varchar(max))");
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, 'SQLSRV_PHPTYPE_UNKNOWN', SQLSRV_SQLTYPE_VARCHAR('max'))));
    print handle_errors() . "\n";    
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, 6, SQLSRV_SQLTYPE_VARCHAR('max'))));
    print handle_errors() . "\n";    
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("sqlsrv_param_query_errors");
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
                     
        ParamQueryError_PhpType_Mismatch($conn);
        ParamQueryError_Dir_Invalid($conn);
        ParamQueryError_PhpType_Encoding($conn);
        ParamQueryError_PhpType_Invalid($conn);

        sqlsrv_close($conn);                   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_param_query_errors");
}

Repro();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_param_query_errors' test...
An invalid direction for parameter 2 was specified. SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, and SQLSRV_PARAM_INOUT are valid values.
An invalid direction for parameter 2 was specified. SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, and SQLSRV_PARAM_INOUT are valid values.
An invalid PHP type for parameter 2 was specified.
An invalid PHP type for parameter 2 was specified.
An invalid PHP type for parameter 2 was specified.

Done
...Test 'sqlsrv_param_query_errors' completed successfully.