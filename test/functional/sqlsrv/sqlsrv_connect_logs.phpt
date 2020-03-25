--TEST--
Test functions return FALSE for errors with logging
--DESCRIPTION--
Similar to sqlsrv_connect.phpt but also test different settings of logging activities
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
    sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_ALL);

    require_once('MsSetup.inc');

    $conn = sqlsrv_connect($server, array( "Driver" => "Wrong Driver" ));
    if ($conn !== false) {
        fatalError("sqlsrv_connect should have returned false.");
    }

    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_NOTICE);
    $conn = sqlsrv_connect($server, array( "uid" => $uid , "pwd" => $pwd ));

    if ($conn === false) {
        fatalError("sqlsrv_connect should have connected.");
    }

    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ERROR);
    $stmt = sqlsrv_query($conn, "SELECT * FROM some_bogus_table");
    if ($stmt !== false) {
        fatalError("sqlsrv_query should have returned false.");
    }

    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_WARNING);
    
    sqlsrv_close($conn);
?>
--EXPECTF--
sqlsrv.LogSubsystems = -1
sqlsrv_connect: entering
sqlsrv_connect: SQLSTATE = IMSSP
sqlsrv_connect: error code = -106
sqlsrv_connect: message = Invalid value Wrong Driver was specified for Driver option.
sqlsrv_configure: entering
sqlsrv.LogSeverity = 4
sqlsrv_connect: entering
sqlsrv_configure: entering
sqlsrv_query: SQLSTATE = 42S02
sqlsrv_query: error code = 208
sqlsrv_query: message = %s[SQL Server]Invalid object name 'some_bogus_table'.
