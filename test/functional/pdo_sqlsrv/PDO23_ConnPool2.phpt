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

function ConnectionTest($noPasses)
{
    include 'MsSetup.inc';

    $testName = "PDO Connection Pooling";
    StartTest($testName);


    $optionsPool = null;
    $optionsNotPool = null;

    if ($dsnMode)
    {
        $optionsPool    = "APP=Microsoft PHP;ConnectionPooling=1;";
        $optionsNotPool = "APP=Microsoft PHP;ConnectionPooling=0;";
    }
    else
    {
        $optionsPool    = array('APP'=>'Microsoft PHP', 'ConnectionPooling'=>1);
        $optionsNotPool = array('APP'=>'Microsoft PHP', 'ConnectionPooling'=>0);
    }

    // Establish a pool connection
    $conn1 = DoConnect($optionsPool);
    $conn1ID = ConnectionID($conn1);
    $conn1 = null;

    // Verifies that non-pool connections have a different id
    for ($i = 0; $i < $noPasses; $i++)
    {
        $conn2 = DoConnect($optionsNotPool);
        $conn2ID = ConnectionID($conn2);
        $conn2 = null;
        if ($conn1ID == $conn2ID)
        {
            echo "A different connection id was expected: $conn1ID => $conn2ID\n";
            break;
        }
        $conn2 = null;
    }


    // Verifies that pool connections have the same id
    for ($i = 0; $i < $noPasses; $i++)
    {
        $conn2 = DoConnect($optionsPool);
        $conn2ID = ConnectionID($conn2);
        $conn2 = null;
        if ($conn1ID != $conn2ID)
        {
            echo "The same connection id was expected: $conn1ID => $conn2ID\n";
            break;
        }
        $conn2 = null;
    }
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
    $query = "SELECT @@SPID FROM master.dbo.sysprocesses WHERE (program_name='Microsoft PHP')";
    $connID = null;

    $stmt = $conn->query($query);
    $row = $stmt->fetch();
    if ($row)
    {
        $connID = $row[0];
    }
    else
    {
        echo "Failed to retrieve connection id\n";
    }
    $stmt = null;
    return ($connID);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        ConnectionTest(5);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO Connection Pooling" completed successfully.

