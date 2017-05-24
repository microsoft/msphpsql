--TEST--
Driver Setup Test
--DESCRIPTION--
Verifies the logging facility by checking the ability to set
and retrieve the values of “LogSubsystem” and "LogSeverity”
parameters.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function LoggingSetup()
{
    $testName = "Driver Logging Setup";
    StartTest($testName);

    Configure('LogSubsystems', -1);
    Configure('LogSubsystems', 1);
    Configure('LogSubsystems', 2);
    Configure('LogSubsystems', 4);
    Configure('LogSubsystems', 8);
    Configure('LogSubsystems', 0);

    Configure('LogSeverity', -1);
    Configure('LogSeverity', 1);
    Configure('LogSeverity', 2);
    Configure('LogSeverity', 4);
    
    Configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);
    Configure('LogSeverity', SQLSRV_LOG_SEVERITY_ERROR);

    EndTest($testName);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        LoggingSetup();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

sqlsrv_configure('WarningsReturnAsError', 0);
Repro();

?>
--EXPECT--
Test "Driver Logging Setup" completed successfully.