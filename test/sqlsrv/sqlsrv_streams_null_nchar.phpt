--TEST--
Populate different unicode character fields using null stream data as inputs
--FILE--
﻿﻿<?php
include 'tools.inc';

function NullStream_Char2Stream($conn)
{
    $tableName = GetTempTableName();
    
    // create a test table 
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_nchar] nchar(512), [c3_nvarchar] nvarchar(512), [c4_nvarchar_max] nvarchar(max), [c5_ntext] ntext)");
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_nchar, c3_nvarchar, c4_nvarchar_max, c5_ntext) VALUES (?, ?, ?, ?, ?)", array(-187518515, &$fname, &$fname, &$fname, &$fname));
    sqlsrv_free_stmt($stmt);
    
    FetchData($conn, $tableName);
}

function FetchData($conn, $tableName)
{
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName");
    sqlsrv_execute($stmt);
    $result = sqlsrv_fetch($stmt);
    $numfields = sqlsrv_num_fields($stmt);
    for ($i = 1; $i < $numfields; $i++)
    {
        $value = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY));
        var_dump($value);    
    }
}

//--------------------------------------------------------------------
// RunTest
//
//--------------------------------------------------------------------
function RunTest()
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

RunTest();

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