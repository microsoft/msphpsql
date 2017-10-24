--TEST--
Client Info Test
--DESCRIPTION--
Verifies the functionality of "sqlsrv_client_info".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function clientInfo()
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

    $driverName = isWindows() ? 'DriverDllName' : 'DriverName';

    showInfo($clientinfo1, 'ExtensionVer');
    showInfo($clientinfo1, $driverName);
    showInfo($clientinfo1, 'DriverVer');
    showInfo($clientinfo1, 'DriverODBCVer');

    sqlsrv_close($conn1);

    endTest($testName);
}

function showInfo($clientInfo, $infoTag)
{
    $info = $clientInfo[$infoTag];
    trace("$infoTag\t= $info\n");
}

try {
    clientInfo();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Connection - Client Info" completed successfully.
