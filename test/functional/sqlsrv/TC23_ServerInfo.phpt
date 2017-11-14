--TEST--
Server Info Test
--DESCRIPTION--
Verifies the functionality of "sqlsrv_server_info".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function serverInfo()
{
    $testName = "Connection - Server Info";
    startTest($testName);

    setup();
    $conn1 = connect();

    $serverinfo1 = sqlsrv_server_info($conn1);
    $count1 = count($serverinfo1);
    if ($count1 != 3) {
        die("Unexpected size for server_info array: ".$count1);
    }

    showInfo($serverinfo1, 'CurrentDatabase');
    showInfo($serverinfo1, 'SQLServerName');
    showInfo($serverinfo1, 'SQLServerVersion');

    sqlsrv_close($conn1);

    endTest($testName);
}

function showInfo($serverInfo, $infoTag)
{
    $info = $serverInfo[$infoTag];
    if (traceMode()) {
        echo "$infoTag\t";
        if (strlen($infoTag) <= 15) {
            echo "\t";
        }
        echo "$info\n";
    }
}

try {
    serverInfo();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Connection - Server Info" completed successfully.
