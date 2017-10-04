--TEST--
Client Info Test
--DESCRIPTION--
Verifies the functionality of "sqlsrv_client_info�.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function ClientInfo()
{
    $testName = "Connection - Client Info";
    startTest($testName);

    setup();
    $conn1 = connect();

    $clientinfo1 = sqlsrv_client_info($conn1);
    $count1 = count($clientinfo1);
    if ($count1 != 4) {
        die("Unexpected size for client_info array: ".$count1);
    }

    $driverName = 'DriverDllName';
    $uname = php_uname();

    if (isWindows()) {
        $driverName = 'DriverDllName';
    } else { // other than Windows
        $driverName = 'DriverName';
    }

    ShowInfo($clientinfo1, 'ExtensionVer');
    ShowInfo($clientinfo1, $driverName);
    ShowInfo($clientinfo1, 'DriverVer');
    ShowInfo($clientinfo1, 'DriverODBCVer');

    sqlsrv_close($conn1);

    endTest($testName);
}

function ShowInfo($clientInfo, $infoTag)
{
    $info = $clientInfo[$infoTag];
    trace("$infoTag\t= $info\n");
}


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        ClientInfo();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECT--
Test "Connection - Client Info" completed successfully.
