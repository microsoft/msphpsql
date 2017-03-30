--TEST--
Populate different test tables with character fields using empty stream data as inputs
--FILE--
﻿﻿<?php
include 'tools.inc';

function EmptyStream_Char2Stream($conn, $fileName)
{
    $tableName = GetTempTableName();
    
    // create another test table a char(512) column
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_char] char(512))");
    sqlsrv_free_stmt($stmt);

    $fname = fopen($fileName, "r");    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_char) VALUES (?, ?)", array(463787351, &$fname), array('SendStreamParamsAtExec' => 0));
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
    
    FetchData($conn, $tableName);

    // create another test table with a varchar(512) column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar] varchar(512))");
    sqlsrv_free_stmt($stmt);

    $fname = fopen($fileName, "r");    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar) VALUES (?, ?)", array(357113758, &$fname), array('SendStreamParamsAtExec' => 0));
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
    
    FetchData($conn, $tableName);
    
    // create another test table with a varchar(max) column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varchar_max] varchar(max))");
    sqlsrv_free_stmt($stmt);

    $fname = fopen($fileName, "r");    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1120737010, &$fname), array('SendStreamParamsAtExec' => 0));
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
    
    FetchData($conn, $tableName);
    
    // create another test table with a text column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_text] text)");
    sqlsrv_free_stmt($stmt);

    $fname = fopen($fileName, "r");    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_text) VALUES (?, ?)", array(1532347140, &$fname), array('SendStreamParamsAtExec' => 0));
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
    
    FetchData($conn, $tableName);
}

function FetchData($conn, $tableName)
{
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName");
    sqlsrv_execute($stmt);
    $result = sqlsrv_fetch($stmt);
    $stream = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    var_dump($stream);

    sqlsrv_execute($stmt);
    $result = sqlsrv_fetch($stmt);
    $value = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY));
    var_dump($value);    
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("sqlsrv_streams_empty_char");
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
                     
        // create an empty file             
        $fileName = "sqlsrv_streams_empty_char.dat";
        $fp = fopen($fileName, "wb");
        fclose($fp);

        EmptyStream_Char2Stream($conn, $fileName);

        // delete the file
        unlink($fileName);
        sqlsrv_close($conn);                   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_streams_empty_char");
}

Repro();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_streams_empty_char' test...
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)

Done
...Test 'sqlsrv_streams_empty_char' completed successfully.