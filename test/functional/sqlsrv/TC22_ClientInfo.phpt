--TEST--
Client Info Test
--DESCRIPTION--
Verifies the functionality of "sqlsrv_client_info”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ClientInfo()
{
    include 'MsSetup.inc';

    $testName = "Connection - Client Info";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    $clientinfo1 = sqlsrv_client_info($conn1);
    $count1 = count($clientinfo1);
    if ($count1 != 4)
    {
        die("Unexpected size for client_info array: ".$count1);
    }
    
    $driverName = 'DriverDllName';
    $uname = php_uname();
    
    if (IsWindows())    
    {
        $driverName = 'DriverDllName';
    } 
    else // other than Windows
    {
        $driverName = 'DriverName';
    }
    
    ShowInfo($clientinfo1, 'ExtensionVer');
    ShowInfo($clientinfo1, $driverName);
    ShowInfo($clientinfo1, 'DriverVer');
    ShowInfo($clientinfo1, 'DriverODBCVer');

    sqlsrv_close($conn1);

    EndTest($testName);
}

function ShowInfo($clientInfo, $infoTag)
{
    $info = $clientInfo[$infoTag];
    Trace("$infoTag\t= $info\n");
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        ClientInfo();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Connection - Client Info" completed successfully.