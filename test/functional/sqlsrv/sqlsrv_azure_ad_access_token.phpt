--TEST--
Test some basics of Azure AD Access Token support
--DESCRIPTION--
This test also expects certain exceptions to be thrown under some conditions.
--SKIPIF--
<?php require('skipif_azure.inc');
      require('skipif_azure_ad_acess_token.inc');  ?>
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
        fatalError("AzureAD access token test: expected to fail with $msg\n");
    }
}

function connectWithEmptyAccessToken($server)
{
    $dummyToken = '';
    $expectedError = 'The Azure AD Access Token is empty. Expected a byte string.';
    
    $connectionInfo = array("AccessToken" => "$dummyToken");
    $conn = sqlsrv_connect($server, $connectionInfo);
    verifyErrorMessage($conn, $expectedError, 'empty token');
    unset($connectionInfo);
}

function connectWithInvalidOptions($server)
{
    $dummyToken = 'abcde';
    $expectedError = 'When using Azure AD Access Token, the connection string must not contain UID, PWD, or Authentication keywords.';
    
    $connectionInfo = array("UID"=>"", "AccessToken" => "$dummyToken");
    $conn = sqlsrv_connect($server, $connectionInfo);
    verifyErrorMessage($conn, $expectedError, 'empty UID provided');
    unset($connectionInfo);

    $connectionInfo = array("PWD"=>"", "AccessToken" => "$dummyToken");
    $conn = sqlsrv_connect($server, $connectionInfo);
    verifyErrorMessage($conn, $expectedError, 'empty PWD provided');
    unset($connectionInfo);

    $connectionInfo = array("UID"=>"uid", "AccessToken" => "$dummyToken");
    $conn = sqlsrv_connect($server, $connectionInfo);
    verifyErrorMessage($conn, $expectedError, 'UID provided');
    unset($connectionInfo);

    $connectionInfo = array("PWD"=>"pwd", "AccessToken" => "$dummyToken");
    $conn = sqlsrv_connect($server, $connectionInfo);
    verifyErrorMessage($conn, $expectedError, 'PWD provided');
    unset($connectionInfo);

    $connectionInfo = array("Authentication"=>"SqlPassword", "AccessToken" => "$dummyToken");
    $conn = sqlsrv_connect($server, $connectionInfo);
    verifyErrorMessage($conn, $expectedError, 'Authentication keyword');
    unset($connectionInfo);
}

function simpleTest($conn)
{
    // Create table
    $tableName = 'Simple';
    $col1 = 'Some simple string value';
    
    dropTable($conn, $tableName);

    $query = "CREATE TABLE $tableName(ID INT IDENTITY(1,1), COL1 VARCHAR(25))";
    $stmt = sqlsrv_query($conn, $query);
    if (!$stmt) {
        fatalError("AzureAD access token test: failed to create a table\n");
    }

    // Insert one row
    $query = "INSERT INTO $tableName VALUES ('$col1')";
    $stmt = sqlsrv_query($conn, $query);
    if (!$stmt) {
        fatalError("AzureAD access token test: failed to insert a row\n");
    }

    // Fetch data
    $query = "SELECT * FROM $tableName";
    $stmt = sqlsrv_query($conn, $query);
    if (!$stmt) {
        fatalError("AzureAD access token test: failed to fetch a table\n");
    }

    while (sqlsrv_fetch($stmt)) {
        $id = sqlsrv_get_field($stmt, 0);
        if ($id != 1) {
            fatalError("AzureAD access token test: fetched id $id unexpected\n");
        }
        $field = sqlsrv_get_field($stmt, 1);
        if ($field !== $col1) {
            fatalError("AzureAD access token test: fetched value $field unexpected\n");
        }
    }
    
    dropTable($conn, $tableName);
}

function connectAzureDB($accToken, $showException)
{
    global $adServer, $adDatabase, $maxAttempts;
    
    $conn = false;
    $connectionInfo = array("Database"=>$adDatabase, "AccessToken"=>$accToken);

    $conn = sqlsrv_connect($adServer, $connectionInfo);
    if ($conn === false) {
        if ($showException) {
            fatalError("Could not connect with Azure AD AccessToken after $maxAttempts retries.\n");
        }
    } else {
        simpleTest($conn);
        
        sqlsrv_close($conn);
    }

    return $conn;
}

// First test some error conditions
connectWithInvalidOptions($server);

// Then, test with an empty access token
connectWithEmptyAccessToken($server);

// Next, test with a valid access token and perform some simple tasks
require_once('access_token.inc');
$maxAttempts = 3;

if ($adServer != 'TARGET_AD_SERVER' && $accToken != 'TARGET_ACCESS_TOKEN') {
    $conn = false;
    $numAttempts = 0;
    do {
        $conn = connectAzureDB($accToken, ($numAttempts == ($maxAttempts - 1)));
        if ($conn === false) {
            $numAttempts++;
            sleep(10);
        }
    } while ($conn === false && $numAttempts < $maxAttempts);
}

echo "Done\n";
?>
--EXPECT--
Done