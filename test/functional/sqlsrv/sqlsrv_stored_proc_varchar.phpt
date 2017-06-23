--TEST--
Test stored procedure that returns a varchar
--FILE--
﻿<?php
include 'MsCommon.inc';

function StoredProc_varchar()
{
    set_time_limit(0);  
    sqlsrv_configure('WarningsReturnAsErrors', 1);  
    
    // Connect
    $conn = Connect();
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
    echo "\nTest begins...\n";
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
Test begins...
Microsoft SQL Server Driver for PHP
Microsoft SQL Server Driver for PHP
Microsoft SQL Server Driver for PHP
Microsoft SQL Server Driver for PHP
Microsoft SQL Server Driver for PHP

Done
Test "sqlsrv_stored_proc_varchar" completed successfully.
