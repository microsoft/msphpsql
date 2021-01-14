--TEST--
Test the Authentication keyword and three options: SqlPassword, ActiveDirectoryIntegrated, and ActiveDirectoryPassword.
--SKIPIF--
<?php require('skipif.inc');
      require('skipif_version_less_than_2k16.inc');  ?>
--FILE--
<?php
require_once("MsSetup.inc");

///////////////////////////////////////////////////////////////////////////////////////////
// Test Azure AD with Authentication=SqlPassword.
//
$connectionInfo = "Database = $databaseName; Authentication = SqlPassword;  TrustServerCertificate = true;";

try {
    $conn = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    echo "Connected successfully with Authentication=SqlPassword.\n";
} catch (PDOException $e) {
    echo "Could not connect with Authentication=SqlPassword.\n";
    print_r($e->getMessage());
    echo "\n";
}

// For details, https://docs.microsoft.com/sql/t-sql/functions/serverproperty-transact-sql
$conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, true);
$stmt = $conn->query("SELECT SERVERPROPERTY('EngineEdition')");
if ($stmt === false) {
    echo "Query failed.\n";
} else {
    $result = $stmt->fetch(PDO::FETCH_NUM);
    $edition = $result[0];
    var_dump($edition);
}

unset($conn);

///////////////////////////////////////////////////////////////////////////////////////////
// Test Azure AD with integrated authentication. This should fail because
// we don't support it.
//
$connectionInfo = "Authentication = ActiveDirectoryIntegrated; TrustServerCertificate = true;";

try {
    $conn = new PDO("sqlsrv:server = $server ; $connectionInfo");
    echo "Connected successfully with Authentication=ActiveDirectoryIntegrated.\n";
    unset($conn);
} catch (PDOException $e) {
    echo "Could not connect with Authentication=ActiveDirectoryIntegrated.\n";
    print_r($e->getMessage());
    echo "\n";
}

///////////////////////////////////////////////////////////////////////////////////////////
// Test Azure AD on an Azure database instance. Replace $azureServer, etc with
// your credentials to test, or this part is skipped.
//
$azureServer = $adServer;
$maxAttempts = 3;

function connectAzureDB($showException)
{
    global $adServer, $adUser, $adPassword, $maxAttempts;
    
    $connectionInfo = "Authentication = ActiveDirectoryPassword; TrustServerCertificate = false";
    
    $conn = false;
    try {
        $conn = new PDO("sqlsrv:server = $adServer; $connectionInfo", $adUser, $adPassword);
        echo "Connected successfully with Authentication=ActiveDirectoryPassword.\n";
    } catch (PDOException $e) {
        if ($showException) {
            echo "Could not connect with ActiveDirectoryPassword after $maxAttempts retries.\n";
            print_r($e->getMessage());
            echo "\n";
        }
    }

    return $conn;
}

if ($azureServer != 'TARGET_AD_SERVER') {
    $conn = false;
    $numAttempts = 0;
    do {
        $conn = connectAzureDB($numAttempts == ($maxAttempts - 1));
        if ($conn === false) {
            $numAttempts++;
            sleep(10);
        }
    } while ($conn === false && $numAttempts < $maxAttempts);
} else {
    echo "Not testing with Authentication=ActiveDirectoryPassword.\n";
}
?>
--EXPECTF--
Connected successfully with Authentication=SqlPassword.
string(1) "%d"
Could not connect with Authentication=ActiveDirectoryIntegrated.
SQLSTATE[IMSSP]: Invalid option for the Authentication keyword. Only SqlPassword, ActiveDirectoryPassword, ActiveDirectoryMsi or ActiveDirectoryServicePrincipal is supported.
%s with Authentication=ActiveDirectoryPassword.
