--TEST--
Test some basics of Azure AD service principal support
--SKIPIF--
<?php 
require_once('skipif.inc');
require_once('MsSetup.inc');

try {
    $conn = new PDO("sqlsrv:server = $server; driver=$driver;", $uid, $pwd);

    $msodbcsqlVer = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)["DriverVer"];
    $msodbcsqlMaj = explode(".", $msodbcsqlVer)[0];
    $msodbcsqlMin = explode(".", $msodbcsqlVer)[1];

    if ($msodbcsqlMaj < 17 || $msodbcsqlMin < 7) {
        die("skip: Requires ODBC driver 17.7 or above");
    }
} catch (PDOException $e) {
    die("skip: Failed to connect in skipif.");
}
?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function simpleTest($conn)
{
    // Create table
    $tableName = 'pdoTestSPA';
    $col1 = 'Testing service principal with pdo';
    
    dropTable($conn, $tableName);

    $query = "CREATE TABLE $tableName(ID INT IDENTITY(1,1), COL1 VARCHAR(50))";
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
        echo "AzureAD service principal test: fetched id $id unexpected\n";
    }
    
    $field = $result[1];
    if ($field !== $col1) {
        echo "AzureAD service principal test: fetched value $field unexpected\n";
    }
    
    dropTable($conn, $tableName);
}

function connectAzureDB($showException)
{
    global $adServer, $adDatabase, $adSPClientId, $adSPClientSecret, $maxAttempts;
    
    $conn = false;
    try {
        $connectionInfo = "Database = $adDatabase; Authentication = ActiveDirectoryServicePrincipal;";
        $conn = new PDO("sqlsrv:server = $adServer; $connectionInfo", $adSPClientId, $adSPClientSecret);
    } catch (PDOException $e) {
        if ($showException) {
            echo "Could not connect with Azure AD Service Principal after $maxAttempts retries.\n";
            print_r($e->getMessage());
            echo PHP_EOL;
        }
    }

    return $conn;
}

// First test connecting to regular sql server
require_once('MsSetup.inc');
try {
    $conn = new PDO("sqlsrv:server = $server; Authentication = ActiveDirectoryServicePrincipal;", $uid, $pwd);
    echo "Expect regular connection to fail\n";
} catch(PDOException $e) {
    // do nothing
}

// Next, test connecting with a valid service principal and perform some simple tasks
$maxAttempts = 3;

try {
    if ($adServer != 'TARGET_AD_SERVER' && $adSPClientId != 'TARGET_ADSP_CLIENT_ID') {
        $conn = false;
        $numAttempts = 0;
        do {
            $conn = connectAzureDB($numAttempts == ($maxAttempts - 1));
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
    print_r($e->getMessage());
    echo PHP_EOL;
}

echo "Done\n";
?>
--EXPECT--
Done