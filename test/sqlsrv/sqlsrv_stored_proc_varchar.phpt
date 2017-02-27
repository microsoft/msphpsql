--TEST--
Test stored procedure that returns a varchar
--FILE--
﻿<?php
include 'tools.inc';

function StoredProc_varchar()
{
    include 'autonomous_setup.php';
       
    set_time_limit(0);  
    sqlsrv_configure('WarningsReturnAsErrors', 1);  
    sqlsrv_get_config('WarningsReturnAsErrors');    
    
    // Connect
    $connectionInfo = array("UID"=>$username, "PWD"=>$password);
    $conn = sqlsrv_connect($serverName, $connectionInfo);
    if( !$conn ) { FatalError("Could not connect.\n"); }

    $procName = GetTempProcName();
    
    $tsql = "CREATE PROC $procName (@p1 VARCHAR(37) OUTPUT, @p2 VARCHAR(21), @p3 VARCHAR(14))
    AS 
    BEGIN 
        SET @p1 = CONVERT(VARCHAR(37), @p2 + @p3) 
    END";
    $stmt = sqlsrv_query($conn, $tsql); 
    sqlsrv_free_stmt($stmt);    
    $retValue = ''; 
    $stmt = sqlsrv_prepare($conn, "{CALL $procName (?, ?, ?)}", array(array(&$retValue, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_NVARCHAR(38)), array('Microsoft SQL Server ', SQLSRV_PARAM_IN), array('Driver for PHP', SQLSRV_PARAM_IN)));  
    $retValue = ''; 
    sqlsrv_execute($stmt);  
    echo("$retValue\n");
    $retValue = 'Microsoft SQL Server Driver for PH';   
    sqlsrv_execute($stmt);  
    echo("$retValue\n");
    $retValue = 'ABCDEFGHIJKLMNOPQRSTUWXYZMicrosoft ';  
    sqlsrv_execute($stmt);  
    echo("$retValue\n");
    $retValue = 'ABCDEFGHIJKLMNOPQRSTUWXYZ_Microsoft SQL Server Driver for PHP';    
    sqlsrv_execute($stmt);  
    echo("$retValue\n");
    $retValue = 'Microsoft SQL Server Driver for';  
    sqlsrv_execute($stmt);  
    echo("$retValue\n");
    sqlsrv_free_stmt($stmt);    
    sqlsrv_close($conn);    
}

function Repro()
{
    StartTest("sqlsrv_stored_proc_varchar");
    try
    {
        StoredProc_varchar();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
    echo "\nDone\n";
    EndTest("sqlsrv_stored_proc_varchar");
}

Repro();

?>
--EXPECT--
﻿
...Starting 'sqlsrv_stored_proc_varchar' test...
Microsoft SQL Server Driver for PHP
Microsoft SQL Server Driver for PHP
Microsoft SQL Server Driver for PHP
Microsoft SQL Server Driver for PHP
Microsoft SQL Server Driver for PHP

Done
...Test 'sqlsrv_stored_proc_varchar' completed successfully.
