--TEST--
Free statement twice 
--FILE--
﻿<?php
include 'MsCommon.inc';

function CloseTwice()
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
    sqlsrv_free_stmt($stmt);   
    sqlsrv_free_stmt($stmt);   
    sqlsrv_close($conn);   
    
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    StartTest("sqlsrv_close_twice");
    try
    {
        CloseTwice();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_close_twice");
}

Repro();

?>
--EXPECTREGEX--
﻿
Warning: sqlsrv_free_stmt\(\): supplied resource is not a valid ss_sqlsrv_stmt resource in .+sqlsrv_close_twice.php on line [0-9]+

Done
Test "sqlsrv_close_twice" completed successfully.
