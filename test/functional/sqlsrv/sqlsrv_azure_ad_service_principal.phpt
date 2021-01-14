--TEST--
Test some basics of Azure AD Service Principal support
--SKIPIF--
<?php 
require_once('skipif.inc');
require_once('MsSetup.inc');

$connectionInfo = array("UID"=>$userName, "PWD"=>$userPassword, "Driver" => $driver);
$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === false) {
    die("skip: Failed to connect in skipif.");
}

$msodbcsqlVer = sqlsrv_client_info($conn)['DriverVer'];
$version = explode(".", $msodbcsqlVer);

if ($version[0] < 17 || $version[1] < 7) {
    die("skip: Requires ODBC driver 17.7 or above");
}
?>
--FILE--
<?php
require_once('MsCommon.inc');

function simpleTest($conn)
{
    // Create table
    $tableName = 'testSPA';
    $col1 = 'Testing service principal';
    
    dropTable($conn, $tableName);

    $query = "CREATE TABLE $tableName(ID INT IDENTITY(1,1), COL1 VARCHAR(50))";
    $stmt = sqlsrv_query($conn, $query);
    if (!$stmt) {
        fatalError("AzureAD service principal test: failed to create a table\n");
    }

    // Insert one row
    $query = "INSERT INTO $tableName VALUES ('$col1')";
    $stmt = sqlsrv_query($conn, $query);
    if (!$stmt) {
        fatalError("AzureAD service principal test: failed to insert a row\n");
    }

    // Fetch data
    $query = "SELECT * FROM $tableName";
    $stmt = sqlsrv_query($conn, $query);
    if (!$stmt) {
        fatalError("AzureAD service principal test: failed to fetch a table\n");
    }

    while (sqlsrv_fetch($stmt)) {
        $id = sqlsrv_get_field($stmt, 0);
        if ($id != 1) {
            fatalError("AzureAD service principal test: fetched id $id unexpected\n");
        }
        $field = sqlsrv_get_field($stmt, 1);
        if ($field !== $col1) {
            fatalError("AzureAD service principal test: fetched value $field unexpected\n");
        }
    }
    
    dropTable($conn, $tableName);
}

function connectAzureDB($showException)
{
    global $adServer, $adDatabase, $adSPClientId, $adSPClientSecret, $maxAttempts;
    
    $conn = false;
    $connectionInfo = array("Database"=>$adDatabase, 
                            "Authentication"=>"ActiveDirectoryServicePrincipal",
                            "UID"=>$adSPClientId,
                            "PWD"=>$adSPClientSecret);

    $conn = sqlsrv_connect($adServer, $connectionInfo);
    if ($conn === false) {
        if ($showException) {
            fatalError("Could not connect with Azure AD Service Principal after $maxAttempts retries.\n");
        }
    } else {
        simpleTest($conn);
        
        sqlsrv_close($conn);
    }

    return $conn;
}

// Try connecting to an invalid server. Expect this to fail.
$connectionInfo = array("Authentication"=>"ActiveDirectoryServicePrincipal");
$conn = sqlsrv_connect('invalidServer', $connectionInfo);
if ($conn) {
    fatalError("AzureAD Service Principal test: expected to fail with invalidServer\n");
}

// Next, test connecting with Service Principal
$maxAttempts = 3;

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
}

echo "Done\n";
?>
--EXPECT--
Done