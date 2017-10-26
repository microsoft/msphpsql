--TEST--
Driver setup Test
--DESCRIPTION--
Verifies the logging facility by checking the ability to set
and retrieve the values of "LogSubsystem" and "LogSeverity"
parameters.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function loggingSetup()
{
    $testName = "Driver Logging setup";
    startTest($testName);

    configure('LogSubsystems', -1);
    configure('LogSubsystems', 1);
    configure('LogSubsystems', 2);
    configure('LogSubsystems', 4);
    configure('LogSubsystems', 8);
    configure('LogSubsystems', 0);

    configure('LogSeverity', -1);
    configure('LogSeverity', 1);
    configure('LogSeverity', 2);
    configure('LogSeverity', 4);

    configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);
    configure('LogSeverity', SQLSRV_LOG_SEVERITY_ERROR);

    endTest($testName);
}

sqlsrv_configure('WarningsReturnAsError', 0);
try {
    loggingSetup();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Driver Logging setup" completed successfully.
