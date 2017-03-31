--TEST--
Populate different test tables with binary fields using empty stream data as inputs
--FILE--
﻿﻿<?php
include 'tools.inc';

function ComplexTransaction($conn)
{
    $tableName = 'emptyStreamTest'; // GetTempTableName();
    
    // create a test table with a binary(512) column
    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_real] real)");
    sqlsrv_free_stmt($stmt);

    $count = 10;
    InsertData($conn, $tableName, $count);
    
    $stmt1 = sqlsrv_prepare($conn, "SELECT * FROM $tableName");
    $stmt2 = sqlsrv_prepare($conn, "DELETE TOP(3) FROM $tableName");

    
    // FetchData($conn, $tableName);

    // $tableName = GetTempTableName();
    
    // // create another test table with a varbinary(512) column
    // $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varbinary] varbinary(512))");
    // sqlsrv_free_stmt($stmt);

    // $fname = fopen($fileName, "r");    
    // $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varbinary) VALUES (?, ?)", array(-1696120652, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(512))));
    // sqlsrv_free_stmt($stmt);
    // fclose($fname);
   
    // FetchData($conn, $tableName);

    // // create another test table with a varbinary(max) column
    // $tableName = GetTempTableName();

    // $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_varbinary_max] varbinary(max))");
    // sqlsrv_free_stmt($stmt);

    // $fname = fopen($fileName, "r");    
    // $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varbinary_max) VALUES (?, ?)", array(1184245066, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))));
    // sqlsrv_free_stmt($stmt);
    // fclose($fname);
    
    // FetchData($conn, $tableName);
    
    // // create another test table with an image column
    // $tableName = GetTempTableName();

    // $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_image] image)");
    // sqlsrv_free_stmt($stmt);

    // $fname = fopen($fileName, "r");    
    // $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_image) VALUES (?, ?)", array(1157651990, array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_IMAGE)));
    // sqlsrv_free_stmt($stmt);
    // fclose($fname);
    
    // FetchData($conn, $tableName);    
}

function InsertData($conn, $tableName, $count)
{
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_real) VALUES (?, ?)", array(&$v1, &$v2));

    for ($i = 0; $i < $count; $i++)
    {
        $v1 = $i + 1;
        $v2 = $v1 * 1.5;

        sqlsrv_execute($stmt); 
    }
}

function FetchData($conn, $tableName)
{
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName");
    sqlsrv_execute($stmt);
    sqlsrv_fetch($stmt);
    $value = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    var_dump($value);            
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("sqlsrv_fetch_complex_transactions");
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
        
        ComplexTransaction($conn);

        sqlsrv_close($conn);                   
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_fetch_complex_transactions");
}

Repro();

?>
--EXPECT--
﻿﻿
...Starting 'sqlsrv_fetch_complex_transactions' test...

Done
...Test 'sqlsrv_fetch_complex_transactions' completed successfully.