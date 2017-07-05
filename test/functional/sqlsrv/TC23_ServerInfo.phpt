--TEST--
Server Info Test
--DESCRIPTION--
Verifies the functionality of “sqlsrv_server_info”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ServerInfo()
{
    include 'MsSetup.inc';

    $testName = "Connection - Server Info";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    $serverinfo1 = sqlsrv_server_info($conn1);  
    $count1 = count($serverinfo1);  
    if ($count1 != 3)
    {
        die("Unexpected size for server_info array: ".$count1);
    }

    ShowInfo($serverinfo1, 'CurrentDatabase');
    ShowInfo($serverinfo1, 'SQLServerName');
    ShowInfo($serverinfo1, 'SQLServerVersion');

    sqlsrv_close($conn1);

    EndTest($testName);
}

function ShowInfo($serverInfo, $infoTag)
{
    $info = $serverInfo[$infoTag];
    if (TraceMode())
    {
        echo "$infoTag\t";
        if (strlen($infoTag) <= 15)
        {
            echo "\t";
        }
        echo "$info\n";
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        ServerInfo();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Connection - Server Info" completed successfully.