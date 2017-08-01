--TEST--
PDO Connection Test
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

    $testName = "PDO Connection";
    StartTest($testName);
    
    // Invalid connection attempt => errors are expected
    Trace("Invalid connection attempt (to a non-existing server) ....\n");
    $conn1 = PDOConnect('PDO', "InvalidServerName", $uid, $pwd, false);
    if ($conn1)
    {
        printf("Invalid connection attempt should have failed.\n");
    }
    $conn1 = null;


    // Valid connection attempt => no errors are expected
    Trace("\nValid connection attempt (to $server) ....\n");
    $conn2 = Connect();
    $conn2 = null;

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
Test "PDO Connection" completed successfully.

