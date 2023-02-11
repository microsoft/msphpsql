--TEST--
Test some error conditions of Azure AD Managed Identity support
--DESCRIPTION--
This test expects certain exceptions to be thrown under some conditions.
--SKIPIF--
<?php 
require('skipif.inc');
require('skipif_Appveyor.inc');
require('skipif_unix.inc');
?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function verifyErrorMessage($exception, $expectedError, $msg)
{
    if (strpos($exception->getMessage(), $expectedError) === false) {
        echo "AzureAD Managed Identity test: expected to fail with $msg\n";

        print_r($exception->getMessage());
        echo "\n";
    }
}

function connectInvalidServer()
{
    global $server, $driver, $uid, $pwd;
    
    try {
        $conn = new PDO("sqlsrv:server = $server; driver=$driver;", $uid, $pwd);
        
        $msodbcsqlVer = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)["DriverVer"];
        $version = explode(".", $msodbcsqlVer);

        if ($version[0] < 17 || $version[1] < 3) {
            //skip the rest of this test, which requires ODBC driver 17.3 or above
            return;
        }
        unset($conn);

        // Try connecting to an invalid server, should get an exception from ODBC
        $connectionInfo = "Authentication = ActiveDirectoryMsi;";
        $testCase = 'invalidServer';
        try {
            $conn = new PDO("sqlsrv:server = invalidServer; $connectionInfo", null, null);
            echo $message . $testCase . PHP_EOL;
        } catch(PDOException $e) {
            echo "Failed to connect\n";
            print_r($e->getMessage());
            echo "\n";
        }
    } catch(PDOException $e) {
        print_r($e->getMessage());
    }
}

function connectInvalidServerWithUser()
{
    global $server, $driver, $uid, $pwd;
    
    try {
        $conn = new PDO("sqlsrv:server = $server; driver=$driver;", $uid, $pwd);
        
        $msodbcsqlVer = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)["DriverVer"];
        $version = explode(".", $msodbcsqlVer);

        if ($version[0] < 17 || $version[1] < 3) {
            //skip the rest of this test, which requires ODBC driver 17.3 or above
            return;
        }
        unset($conn);

        // Try connecting to an invalid server, should get an exception from ODBC
        $connectionInfo = "Authentication = ActiveDirectoryMsi;";
        $user = "user";
        $testCase = 'invalidServer';
        try {
            $conn = new PDO("sqlsrv:server = invalidServer; $connectionInfo", $user, null);
            echo $message . $testCase . PHP_EOL;
        } catch(PDOException $e) {
            echo "Failed to connect\n";
            print_r($e->getMessage());
            echo "\n";
        }
    } catch(PDOException $e) {
        print_r($e->getMessage());
    }
}

require_once('MsSetup.inc');

// Make a connection to an invalid server
// Expect to get two error messages
connectInvalidServer();
connectInvalidServerWithUser();

echo "Done\n";
?>
--EXPECT--
Failed to connect
SQLSTATE[08001]: [Microsoft][ODBC Driver 17 for SQL Server]Named Pipes Provider: Could not open a connection to SQL Server [53]. 
Failed to connect
SQLSTATE[08001]: [Microsoft][ODBC Driver 17 for SQL Server]Named Pipes Provider: Could not open a connection to SQL Server [53]. 
Done
