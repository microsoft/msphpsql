--TEST--
Test some error conditions of Azure AD Managed Identity support
--DESCRIPTION--
This test expects certain exceptions to be thrown under some conditions.
--SKIPIF--
<?php require('skipif.inc');?>
--FILE--
<?php
require_once("MsCommon.inc");

function verifyErrorMessage($conn, $expectedError, $msg)
{
    if ($conn === false) {
        if (strpos(sqlsrv_errors($conn)[0]['message'], $expectedError) === false) {
            print_r(sqlsrv_errors());
        }
    } else {
        fatalError("AzureAD Managed Identity test: expected to fail with $msg\n");
    }
}

function connectInvalidServer()
{
    global $server, $driver, $userName, $userPassword;
    
    $connectionInfo = array("UID"=>$userName, "PWD"=>$userPassword, "Driver" => $driver);
    $conn = sqlsrv_connect($server, $connectionInfo);
    if ($conn === false) {
        fatalError("Failed to connect in connectInvalidServer.");
    }

    $msodbcsqlVer = sqlsrv_client_info($conn)['DriverVer'];
    $version = explode(".", $msodbcsqlVer);

    if ($version[0] < 17 || $version[1] < 3) {
        //skip the rest of this test, which requires ODBC driver 17.3 or above
        return;
    }
    sqlsrv_close($conn);

    // Try connecting to an invalid server, should get an exception from ODBC
    $connectionInfo = array("Authentication"=>"ActiveDirectoryMsi");
    $conn = sqlsrv_connect('invalidServer', $connectionInfo);
    if ($conn) {
        fatalError("AzureAD Managed Identity test: expected to fail with invalidServer\n");
    } else {
        // TODO: check the exception message here, using verifyErrorMessage() 
    }
}

// Make a connection to an invalid server
connectInvalidServer();

echo "Done\n";
?>
--EXPECT--
Done