--TEST--
Connection Test
--DESCRIPTION--
Checks whether the driver can successfully establish a database connection.
Verifies as well that invalid connection attempts fail as expected.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ConnectionTest()
{
    include 'MsSetup.inc';

    $testName = "Connection";
    StartTest($testName);

    Setup();
    
    // Invalid connection attempt => errors are expected
    Trace("Invalid connection attempt (to a non-existing server) ....\n");
    $conn1 = sqlsrv_connect('InvalidServerName');
    if ($conn1 === false)
    {
        handle_errors();
    }
    else
    {
        die("Invalid connection attempt should have failed.");
    }

    // Valid connection attempt => no errors are expected
    Trace("\nValid connection attempt (to $server) ....\n");
    $conn2 = Connect();
    $errors = sqlsrv_errors(SQLSRV_ERR_ERRORS); 
    if(count($errors) != 0)
    {
        die("No errors were expected on valid connection attempts.");
    }
    sqlsrv_close($conn2);

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
        ConnectionTest();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Connection" completed successfully.

