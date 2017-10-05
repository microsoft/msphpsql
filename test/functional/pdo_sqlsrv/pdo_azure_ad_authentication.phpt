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

try
{
    $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo", $uid, $pwd );
    echo "Connected successfully with Authentication=SqlPassword.\n";
}
catch( PDOException $e )
{
    echo "Could not connect with Authentication=SqlPassword.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$stmt = $conn->query( "SELECT count(*) FROM cd_info" );
if ( $stmt === false )
{
    echo "Query failed.\n";
}
else
{
    $result = $stmt->fetch();
    var_dump( $result );
}

$conn = null;

///////////////////////////////////////////////////////////////////////////////////////////
// Test Azure AD with integrated authentication. This should fail because
// we don't support it.
//
$connectionInfo = "Authentication = ActiveDirectoryIntegrated; TrustServerCertificate = true;";

try
{
    $conn = new PDO( "sqlsrv:server = $server ; $connectionInfo" );
    echo "Connected successfully with Authentication=ActiveDirectoryIntegrated.\n";
    $conn = null;
}
catch( PDOException $e )
{
    echo "Could not connect with Authentication=ActiveDirectoryIntegrated.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

///////////////////////////////////////////////////////////////////////////////////////////
// Test Azure AD on an Azure database instance. Replace $azureServer, etc with
// your credentials to test, or this part is skipped.
//
$azureServer = $adServer;
$azureDatabase = $adDatabase;
$azureUsername = $adUser;
$azurePassword = $adPassword;

if ($azureServer != 'TARGET_AD_SERVER')
{
    $connectionInfo = "Authentication = ActiveDirectoryPassword; TrustServerCertificate = false";

    try
    {
        $conn = new PDO( "sqlsrv:server = $azureServer ; $connectionInfo", $azureUsername, $azurePassword );
        echo "Connected successfully with Authentication=ActiveDirectoryPassword.\n";
    }
    catch( PDOException $e )
    {
        echo "Could not connect with ActiveDirectoryPassword.\n";
        print_r( $e->getMessage() );
        echo "\n";
    }
}
else
{
    echo "Not testing with Authentication=ActiveDirectoryPassword.\n";
}
?>
--EXPECTF--
Connected successfully with Authentication=SqlPassword.
array(2) {
  [""]=>
  string(1) "7"
  [0]=>
  string(1) "7"
}
Could not connect with Authentication=ActiveDirectoryIntegrated.
SQLSTATE[IMSSP]: Invalid option for the Authentication keyword. Only SqlPassword or ActiveDirectoryPassword is supported.
%s with Authentication=ActiveDirectoryPassword.
