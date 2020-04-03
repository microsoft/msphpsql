--TEST--
Test new connection keyword ColumnEncryption
--DESCRIPTION--
Some test cases return errors as expected. For testing purposes, an enclave enabled 
SQL Server and the HGS server are the same instance. If the server is HGS enabled,
the error message of one test case is not the same.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
$msodbcsqlMaj = "";
$hgsEnabled = true;

try {
    $conn = new PDO("sqlsrv:server = $server", $uid, $pwd);
    $msodbcsqlVer = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)['DriverVer'];
    $version = explode(".", $msodbcsqlVer);
    $msodbcsqlMaj = $version[0];

    // Next, check if the server is HGS enabled
    $serverInfo = $conn->getAttribute(PDO::ATTR_SERVER_INFO);
    if (strpos($serverInfo['SQLServerName'], 'PHPHGS') === false) {
        $hgsEnabled = false;
    }
} catch (PDOException $e) {
    echo "Failed to connect\n";
    print_r($e->getMessage());
    echo "\n";
}

testColumnEncryption($server, $uid, $pwd, $msodbcsqlMaj);
echo "Done";


function verifyOutput($PDOerror, $expected, $caseNum)
{
    if (strpos($PDOerror->getMessage(), $expected) === false) {
        echo "Test case $caseNum failed:\n";
        print_r($PDOerror->getMessage());
        echo "\n";
    }
}

function testColumnEncryption($server, $uid, $pwd, $msodbcsqlMaj)
{
    global $hgsEnabled;
    
    // Only works for ODBC 17
    ////////////////////////////////////////
    $connectionInfo = "ColumnEncryption = Enabled;";
    try {
        $conn = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    } catch (PDOException $e) {
        if ($msodbcsqlMaj < 17) {
            $expected = "The Always Encrypted feature requires Microsoft ODBC Driver 17 for SQL Server.";
            verifyOutput($e, $expected, "1");
        } else {
            echo "Test case 1 failed:\n";
            print_r($e->getMessage());
            echo "\n";
        }
    }

    // Works for ODBC 17, ODBC 13
    ////////////////////////////////////////
    $connectionInfo = "ColumnEncryption = Disabled;";
    try {
        $conn = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    } catch (PDOException $e) {
        if ($msodbcsqlMaj < 13) {
            $expected = "Invalid connection string attribute";
            verifyOutput($e, $expected, "2");
        } else {
            echo "Test case 2 failed:\n";
            print_r($e->getMessage());
            echo "\n";
        }
    }

    // should fail for all ODBC drivers
    $expected = "Invalid value specified for connection string attribute 'ColumnEncryption'";
    if ($hgsEnabled) {
        $expected = "Requested attestation protocol is invalid.";
    }
    
    ////////////////////////////////////////
    $connectionInfo = "ColumnEncryption = false;";
    try {
        $conn = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    } catch (PDOException $e) {
        verifyOutput($e, $expected, "3");
    }

    // should fail for all ODBC drivers
    ////////////////////////////////////////
    $connectionInfo = "ColumnEncryption = 1;";
    try {
        $conn = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    } catch (PDOException $e) {
        verifyOutput($e, $expected, "4");
    }
}
?>
--EXPECT--
Done