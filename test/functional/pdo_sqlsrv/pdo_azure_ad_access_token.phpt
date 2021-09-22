--TEST--
Test some basics of Azure AD Access Token support
--DESCRIPTION--
This test also expects certain exceptions to be thrown under some conditions.
--SKIPIF--
<?php require('skipif_azure.inc');
      require('skipif_azure_ad_acess_token.inc');  ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function verifyErrorMessage($exception, $expectedError, $msg)
{
    if (strpos($exception->getMessage(), $expectedError) === false) {
        echo "AzureAD access token test: expected to fail with $msg\n";

        print_r($exception->getMessage());
        echo "\n";
    }
}

function connectWithEmptyAccessToken($server)
{
    $dummyToken = '';
    $expectedError = 'The Azure AD Access Token is empty. Expected a byte string.';
    
    $connectionInfo = "AccessToken = $dummyToken;";
    $testCase = 'empty token';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo");
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);
}

function connectWithInvalidOptions($server)
{
    $dummyToken = 'abcde';
    $expectedError = 'When using Azure AD Access Token, the connection string must not contain UID, PWD, or Authentication keywords.';
    $message = 'AzureAD access token test: expected to fail with ';
    
    $uid = '';
    $connectionInfo = "AccessToken = $dummyToken;";
    $testCase = 'empty UID provided';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo", $uid);
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);

    $pwd = '';
    $connectionInfo = "AccessToken = $dummyToken;";
    $testCase = 'empty PWD provided';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo", null, $pwd);
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);

    $uid = 'uid';
    $connectionInfo = "AccessToken = $dummyToken;";
    $testCase = 'UID provided';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo", $uid);
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);

    $pwd = '';
    $connectionInfo = "AccessToken = $dummyToken;";
    $testCase = 'PWD provided';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo", null, $pwd);
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);
    
    $connectionInfo = "Authentication = SqlPassword; AccessToken = $dummyToken;";
    $testCase = 'Authentication keyword';
    try {
        $conn = new PDO("sqlsrv:server = $server; $connectionInfo");
        echo $message . $testCase . PHP_EOL;
    } catch(PDOException $e) {
        verifyErrorMessage($e, $expectedError, $testCase);
    }
    unset($connectionInfo);
}

function simpleTest($conn)
{
    // Create table
    $tableName = 'Simple';
    $col1 = 'Some simple string value';
    
    dropTable($conn, $tableName);

    $query = "CREATE TABLE $tableName(ID INT IDENTITY(1,1), COL1 VARCHAR(25))";
    $stmt = $conn->query($query);

    // Insert one row
    $query = "INSERT INTO $tableName VALUES ('$col1')";
    $stmt = $conn->query($query);

    // Fetch data
    $query = "SELECT * FROM $tableName";
    $stmt = $conn->query($query);

    $result = $stmt->fetch(PDO::FETCH_NUM);
    $id = $result[0];
    if ($id != 1) {
        echo "AzureAD access token test: fetched id $id unexpected\n";
    }
    
    $field = $result[1];
    if ($field !== $col1) {
        echo "AzureAD access token test: fetched value $field unexpected\n";
    }
    
    dropTable($conn, $tableName);
}

function connectAzureDB($accToken, $showException)
{
    global $adServer, $adDatabase, $maxAttempts;
    
    $conn = false;
    try {
        $connectionInfo = "Database = $adDatabase; AccessToken = $accToken;";
        $conn = new PDO("sqlsrv:server = $adServer; $connectionInfo");
    } catch (PDOException $e) {
        if ($showException) {
            echo "Could not connect with Azure AD AccessToken after $maxAttempts retries.\n";
            print_r($e->getMessage());
            echo PHP_EOL;
        }
    }

    return $conn;
}

// First test some error conditions
require_once('MsSetup.inc');
connectWithInvalidOptions($server);

// Then, test with an empty access token
connectWithEmptyAccessToken($server);

// Next, test with a valid access token and perform some simple tasks
require_once('access_token.inc');
$maxAttempts = 3;

try {
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

        // Proceed when successfully connected
        if ($conn) {
            $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
            simpleTest($conn);
            unset($conn);
        }
    }
} catch(PDOException $e) {
    print_r( $e->getMessage() );
    echo PHP_EOL;
}

echo "Done\n";
?>
--EXPECT--
Done