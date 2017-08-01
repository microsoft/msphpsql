--TEST--
PDO Connection Pooling Test
--DESCRIPTION--
Checks whether the driver can successfully establish a database connection
when an URI-based construct is used.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_unix.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ConnectionTest()
{
    include 'MsSetup.inc';

    $testName = "PDO Connection Pooling";
    StartTest($testName);

    $optionsDefault = null;
    $optionsPool = null;
    $optionsNotPool = null;

    if ($dsnMode)
    {
        $optionsDefault = "APP=Microsoft PHP;";
        $optionsPool    = "APP=Microsoft PHP;ConnectionPooling=1;";
        $optionsNotPool = "APP=Microsoft PHP;ConnectionPooling=0;";
    }
    else
    {
        $optionsDefault = array('APP'=>'Microsoft PHP');
        $optionsPool    = array('APP'=>'Microsoft PHP', 'ConnectionPooling'=>1);
        $optionsNotPool = array('APP'=>'Microsoft PHP', 'ConnectionPooling'=>0);
    }

    // Create pool
    $conn1 = DoConnect($optionsPool);
    $conn2 = DoConnect($optionsPool);
    $conn3 = DoConnect($optionsPool);

    $connId1 = ConnectionID($conn1);
    $connId2 = ConnectionID($conn2);
    $connId3 = ConnectionID($conn3);

    $conn1 = null;
    $conn2 = null;
    $conn3 = null;

    $conn4 = DoConnect($optionsDefault);
    if (!IsPoolConnection($conn4, $connId1, $connId2, $connId3))
    {
        echo "Default connection was expected to be a pool connection..\n";
    }
    $conn4 = null;


    $conn5 = DoConnect($optionsPool);
    if (!IsPoolConnection($conn5, $connId1, $connId2, $connId3))
    {
        echo "A pool connection was expected...\n";
    }
    $conn5 = null;

    $conn6 = DoConnect($optionsNotPool);
    if (IsPoolConnection($conn6, $connId1, $connId2, $connId3))
    {
        echo "A pool connection was not expected...\n";
    }
    $conn6 = null;

    EndTest($testName);
}

function DoConnect($options)
{
    include 'MsSetup.inc';

    $conn = null;

    try
    {

        if ($dsnMode)
        {
            $conn = new PDO("sqlsrv:Server=$server;$options", $uid, $pwd);
        }
        else
        {
            $conn = new PDO("sqlsrv:Server=$server", $uid, $pwd, $options);
        }
        $conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_SYSTEM);
    }
    catch (PDOException $e)
    {
        $conn = null;
        TraceEx("\nFailed to connect to $server: ".$e->getMessage(), true);
    }

        return ($conn);
}

function ConnectionID($conn)
{
    $tsql = "SELECT @@SPID FROM master.dbo.sysprocesses WHERE (program_name='Microsoft PHP')";

    $stmt = ExecuteQuery($conn, $tsql);
    $connID = $stmt->fetchColumn(0);
    $stmt->closeCursor();
    $stmt = null;

    return ($connID);
}

function IsPoolConnection($conn, $Id1, $Id2, $Id3)
{
    $connID = ConnectionID($conn);
    if (($connID == $Id1) || ($connID == $Id2) || ($connID == $Id3))
    {
        return (true);
    }
    return (false);
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
--EXPECTF--
Test "PDO Connection Pooling" completed successfully.

