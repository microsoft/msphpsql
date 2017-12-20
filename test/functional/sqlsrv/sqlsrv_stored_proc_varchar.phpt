--TEST--
Test stored procedure that returns a varchar
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿<?php
require_once('MsCommon.inc');

function storedProcVarchar()
{
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();

    $procName = 'sp_varchar';
    dropProc($conn, $procName);

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
    
    dropProc($conn, $procName);
    sqlsrv_close($conn);
}

echo "\nTest begins...\n";
try {
    storedProcVarchar();
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

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
