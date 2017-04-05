--TEST--
Populate different test tables with character fields using empty stream data as inputs
--FILE--
﻿﻿<?php
include 'tools.inc';

function EmptyStream_Char2Stream($conn, $fileName)
{
    $tableName = GetTempTableName();
    
    // create a test table 
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_char] char(512), [c3_varchar] varchar(512), [c4_varchar_max] varchar(max), [c5_text] text)");
    sqlsrv_free_stmt($stmt);

    // insert data
    $fname = fopen($fileName, "r");    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_char) VALUES (?, ?)", array(1, &$fname), array('SendStreamParamsAtExec' => 0));
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
    
    FetchData($conn, $tableName, 1);

    $fname = fopen($fileName, "r");    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c3_varchar) VALUES (?, ?)", array(2, &$fname), array('SendStreamParamsAtExec' => 0));
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
    
    FetchData($conn, $tableName, 2);
    
    $fname = fopen($fileName, "r");    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c4_varchar_max) VALUES (?, ?)", array(3, &$fname), array('SendStreamParamsAtExec' => 0));
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
    
    FetchData($conn, $tableName, 3);
    
    $fname = fopen($fileName, "r");    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c5_text) VALUES (?, ?)", array(4, &$fname), array('SendStreamParamsAtExec' => 0));
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
    
    FetchData($conn, $tableName, 4);
}

function FetchData($conn, $tableName, $value)
{
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName WHERE c1_int = $value");
    sqlsrv_execute($stmt);
    $fld = $value;
    $result = sqlsrv_fetch($stmt);
    $stream = sqlsrv_get_field($stmt, $fld, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    var_dump($stream);
    
    sqlsrv_execute($stmt);
    $result = sqlsrv_fetch($stmt);
    $value = sqlsrv_get_field($stmt, $fld, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY));
    var_dump($value);    
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
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

RunTest();

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