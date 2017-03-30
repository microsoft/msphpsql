--TEST--
Populate different test tables with unicode character fields using null stream data as inputs
--FILE--
﻿﻿<?php
include 'tools.inc';

function NullStream_Char2Stream($conn)
{
    $tableName = GetTempTableName();
    
    // create another test table a char(512) column
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_nchar] nchar(512))");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_nchar) VALUES (?, ?)", array(-187518515, &$fname));
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);

    // create another test table with a varchar(512) column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_nvarchar] nvarchar(512))");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_nvarchar) VALUES (?, ?)", array(-2014452636, &$fname));
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);
    
    // create another test table with a varchar(max) column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_nvarchar_max] nvarchar(max))");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_nvarchar_max) VALUES (?, ?)", array(1742573153, &$fname));
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);
    
    // create another test table with a text column
    $tableName = GetTempTableName();

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_ntext] ntext)");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_ntext) VALUES (?, ?)", array(1477560975, &$fname));
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);
}

function FetchData($conn, $tableName)
{
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName");
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
    StartTest("sqlsrv_streams_null_nchar");
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
                     
        NullStream_Char2Stream($conn);

        sqlsrv_close($conn);                   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_streams_null_nchar");
}

Repro();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_streams_null_nchar' test...
NULL
NULL
NULL
NULL

Done
...Test 'sqlsrv_streams_null_nchar' completed successfully.