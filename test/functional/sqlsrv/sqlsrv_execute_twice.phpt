--TEST--
Free statement twice 
--FILE--
﻿<?php
include 'MsCommon.inc';

function ExecuteTwice()
{  
    set_time_limit(0);  
    sqlsrv_configure('WarningsReturnAsErrors', 1);  

    // Connect
    $conn = Connect();
    if( !$conn ) { FatalError("Could not connect.\n"); }
    
    $tableName = GetTempTableName();  

    $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_int] int, [c2_tinyint] tinyint)");  
    sqlsrv_free_stmt($stmt);   
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");  
    sqlsrv_execute($stmt);
    
    $errors = sqlsrv_errors(SQLSRV_ERR_ALL);    
    $e = $errors[0];    
    $value1 = $e['message'];    
    print "$value1\n";  
    $value2 = $e['code'];   
    print "$value2\n";  
    $value3 = $e['SQLSTATE'];   
    print "$value3\n";  
    
    sqlsrv_free_stmt($stmt);   
    sqlsrv_close($conn);   
    
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("sqlsrv_statement_execute_twice");
    echo "\nTest begins...\n";
    try
    {
        ExecuteTwice();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_statement_execute_twice");
}

Repro();

?>
--EXPECT--
﻿
Test begins...
A statement must be prepared with sqlsrv_prepare before calling sqlsrv_execute.
-23
IMSSP

Done
Test "sqlsrv_statement_execute_twice" completed successfully.
