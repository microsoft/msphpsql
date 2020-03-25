--TEST--
Test functions return FALSE for errors with logging
--DESCRIPTION--
Similar to sqlsrv_connect_logs.phpt but this time test logging to a log file
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require_once('MsSetup.inc');

    $logFilename = 'php_errors.log';
    $logFilepath = dirname(__FILE__).'/'.$logFilename;
    if (file_exists($logFilepath)) {
        unlink($logFilepath);
    }

    ini_set('log_errors', '1');
    ini_set('error_log', $logFilepath);
    ini_set("sqlsrv.LogSeverity", SQLSRV_LOG_SEVERITY_ALL);
    ini_set("sqlsrv.LogSubsystems", SQLSRV_LOG_SYSTEM_ALL);

    $conn = sqlsrv_connect($server, array( "Driver" => "Wrong Driver" ));
    if ($conn !== false) {
        fatalError("sqlsrv_connect should have returned false.");
    }

    ini_set("sqlsrv.LogSeverity", SQLSRV_LOG_SEVERITY_NOTICE);
    $conn = sqlsrv_connect($server, array( "uid" => $uid , "pwd" => $pwd ));

    if ($conn === false) {
        fatalError("sqlsrv_connect should have connected.");
    }

    ini_set("sqlsrv.LogSeverity", SQLSRV_LOG_SEVERITY_ERROR);
    $stmt = sqlsrv_query($conn, "SELECT * FROM some_bogus_table");
    if ($stmt !== false) {
        fatalError("sqlsrv_query should have returned false.");
    }

    ini_set("sqlsrv.LogSeverity", SQLSRV_LOG_SEVERITY_ALL);
    if (file_exists($logFilepath)) {
        echo file_get_contents($logFilepath);
        unlink($logFilepath);
    }
    
    sqlsrv_close($conn);
?>
--EXPECTF--
[%s UTC] sqlsrv_connect: entering
[%s UTC] sqlsrv_connect: SQLSTATE = IMSSP
[%s UTC] sqlsrv_connect: error code = -106
[%s UTC] sqlsrv_connect: message = Invalid value Wrong Driver was specified for Driver option.
[%s UTC] sqlsrv_connect: entering
[%s UTC] sqlsrv_query: SQLSTATE = 42S02
[%s UTC] sqlsrv_query: error code = 208
[%s UTC] sqlsrv_query: message = %s[SQL Server]Invalid object name 'some_bogus_table'.