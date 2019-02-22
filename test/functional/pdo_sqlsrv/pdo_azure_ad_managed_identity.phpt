--TEST--
Test some error conditions of Azure AD Managed Identity support
--DESCRIPTION--
This test expects certain exceptions to be thrown under some conditions.
--SKIPIF--
<?php require('skipif.inc');?>
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

function connectWithInvalidOptions()
{
    global $server;
    
    $message = 'AzureAD Managed Identity test: expected to fail with ';
    $expectedError = 'When using ActiveDirectoryMsi Authentication, PWD must be NULL. UID can be NULL, but if not, an empty string is not accepted';
    
    $uid = '';
    $connectionInfo = "Authentication = ActiveDirectoryMsi;";
    $testCase = 'empty UID provided';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo", $uid);
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);

    $pwd = '';
    $connectionInfo = "Authentication = ActiveDirectoryMsi;";
    $testCase = 'empty PWD provided';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo", null, $pwd);
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);

    $pwd = 'dummy';
    $connectionInfo = "Authentication = ActiveDirectoryMsi;";
    $testCase = 'PWD provided';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo", null, $pwd);
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);

    $expectedError = 'When using Azure AD Access Token, the connection string must not contain UID, PWD, or Authentication keywords.';
    $connectionInfo = "Authentication = ActiveDirectoryMsi; AccessToken = '123';";
    $testCase = 'AccessToken option';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo");
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);
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
            // TODO: check the exception message here
        }
    } catch(PDOException $e) {
        print_r($e->getMessage());
    }
}

require_once('MsSetup.inc');

// Test some error conditions
connectWithInvalidOptions();

// Make a connection to an invalid server
connectInvalidServer();

echo "Done\n";
?>
--EXPECT--
Done