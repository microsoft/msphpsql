--TEST--
Populate different test tables with binary fields using null stream data as inputs. 
--FILE--
﻿﻿<?php
include 'tools.inc';

function NullStream_Bin2String($conn)
{
    $tableName = GetTempTableName();
    
    // create a test table with a varbinary(512) column
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varbinary] varbinary(512))");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varbinary) VALUES (?, ?)", array(-2106133115, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(512))));
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);

    // create another test table with a varbinary(max) column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varbinary_max] varbinary(max))");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varbinary_max) VALUES (?, ?)", array(1209714662, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))));
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);
    
    // create another test table with an image column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_image] image)");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_image) VALUES (?, ?)", array(429203895, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_IMAGE)));
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);    
}

function NullStreamPrep_Bin2String($conn)
{
    $tableName = GetTempTableName();
    
    // create another test table a varbinary(512) column
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varbinary] varbinary(512))");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_varbinary) VALUES (?, ?)", array(-413736480, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(512))));
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);

    // create another test table with a varbinary(max) column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varbinary_max] varbinary(max))");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_varbinary_max) VALUES (?, ?)", array(-210414092, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))));
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);
    
    // create another test table with an image column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_image] image)");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_image) VALUES (?, ?)", array(1657743705, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_IMAGE)));
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);    
}

function FetchData($conn, $tableName)
{
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    $result = sqlsrv_fetch($stmt);
    $value = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    var_dump($value);    
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
{
    StartTest("sqlsrv_streams_null_binary");
    try
    {
        set_time_limit(0);  
        sqlsrv_configure('WarningsReturnAsErrors', 1);  

        require_once("autonomous_setup.php");
        $database = "tempdb";
        
        // Connect
        $connectionInfo = array('Database'=>$database, 'UID'=>$username, 'PWD'=>$password);
        $conn = sqlsrv_connect($serverName, $connectionInfo);
        if( !$conn ) { FatalError("Could not connect.\n"); }
                     
        NullStream_Bin2String($conn);
        NullStreamPrep_Bin2String($conn);

        sqlsrv_close($conn);                   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_streams_null_binary");
}

RunTest();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_streams_null_binary' test...
NULL
NULL
NULL
NULL
NULL
NULL

Done
...Test 'sqlsrv_streams_null_binary' completed successfully.