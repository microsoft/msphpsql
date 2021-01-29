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
$connectionInfo = array( "Database"=>$databaseName, "UID"=>$uid, "PWD"=>$pwd,
                         "Authentication"=>'SqlPassword', "TrustServerCertificate"=>true);

$conn = sqlsrv_connect($server, $connectionInfo);

if ($conn === false) {
    echo "Could not connect with Authentication=SqlPassword.\n";
    var_dump(sqlsrv_errors());
} else {
    echo "Connected successfully with Authentication=SqlPassword.\n";
}

// For details, https://docs.microsoft.com/sql/t-sql/functions/serverproperty-transact-sql
$stmt = sqlsrv_query($conn, "SELECT SERVERPROPERTY('EngineEdition')");
if (sqlsrv_fetch($stmt)) {
    $edition = sqlsrv_get_field($stmt, 0);
    var_dump($edition);
} else {
    echo "Query failed.\n";
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

///////////////////////////////////////////////////////////////////////////////////////////
// Test Azure AD with integrated authentication. This should fail because
// we don't support it.
//
$connectionInfo = array( "Authentication"=>"ActiveDirectoryIntegrated", "TrustServerCertificate"=>true );

$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === false) {
    echo "Could not connect with Authentication=ActiveDirectoryIntegrated.\n";
    $errors = sqlsrv_errors();
    print_r($errors[0]);
} else {
    echo "Connected successfully with Authentication=ActiveDirectoryIntegrated.\n";
    sqlsrv_close($conn);
}

///////////////////////////////////////////////////////////////////////////////////////////
// Test Azure AD on an Azure database instance. Replace $azureServer, etc with
// your credentials to test, or this part is skipped.
//
function connectAzureDB($showException)
{
    global $adServer, $adUser, $adPassword, $maxAttempts;
    
    $connectionInfo = array("UID"=>$adUser, 
                            "PWD"=>$adPassword,
                            "Authentication"=>'ActiveDirectoryPassword',
                            "TrustServerCertificate"=>false );
    
    $conn = false;
    $conn = sqlsrv_connect($adServer, $connectionInfo);
    if ($conn === false) {
        if ($showException) {
            echo "Could not connect with ActiveDirectoryPassword after $maxAttempts retries.\n";
            print_r(sqlsrv_errors());
        }
    } else {
        echo "Connected successfully with Authentication=ActiveDirectoryPassword.\n";
        sqlsrv_close($conn);
    }

    return $conn;
}

$azureServer = $adServer;
$maxAttempts = 3;

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
Array
(
    [0] => IMSSP
    [SQLSTATE] => IMSSP
    [1] => -62
    [code] => -62
    [2] => Invalid option for the Authentication keyword. Only SqlPassword, ActiveDirectoryPassword, ActiveDirectoryMsi or ActiveDirectoryServicePrincipal is supported.
    [message] => Invalid option for the Authentication keyword. Only SqlPassword, ActiveDirectoryPassword, ActiveDirectoryMsi or ActiveDirectoryServicePrincipal is supported.
)
%s with Authentication=ActiveDirectoryPassword.
