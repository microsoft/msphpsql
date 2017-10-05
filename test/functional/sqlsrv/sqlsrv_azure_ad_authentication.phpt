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

$conn = sqlsrv_connect( $server, $connectionInfo );

if( $conn === false )
{
    echo "Could not connect with Authentication=SqlPassword.\n";
    var_dump( sqlsrv_errors() );
}
else
{
    echo "Connected successfully with Authentication=SqlPassword.\n";
}

$stmt = sqlsrv_query( $conn, "SELECT count(*) FROM cd_info" );
if ( $stmt === false )
{
    echo "Query failed.\n";
}
else
{
    $result = sqlsrv_fetch_array( $stmt );
    var_dump( $result );
}

sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );

///////////////////////////////////////////////////////////////////////////////////////////
// Test Azure AD with integrated authentication. This should fail because
// we don't support it.
//
$connectionInfo = array( "Authentication"=>"ActiveDirectoryIntegrated", "TrustServerCertificate"=>true );

$conn = sqlsrv_connect( $server, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect with Authentication=ActiveDirectoryIntegrated.\n";
    $errors = sqlsrv_errors();
    print_r($errors[0]);
}
else
{
    echo "Connected successfully with Authentication=ActiveDirectoryIntegrated.\n";
    sqlsrv_close( $conn );
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
    $connectionInfo = array( "UID"=>$azureUsername, "PWD"=>$azurePassword, 
                         "Authentication"=>'ActiveDirectoryPassword',  "TrustServerCertificate"=>true );

    $conn = sqlsrv_connect( $azureServer, $connectionInfo );
    if( $conn === false )
    {
        echo "Could not connect with ActiveDirectoryPassword.\n";
        print_r( sqlsrv_errors() );
    }
    else
    {
        echo "Connected successfully with Authentication=ActiveDirectoryPassword.\n";
        sqlsrv_close( $conn );
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
  [0]=>
  int(7)
  [""]=>
  int(7)
}
Could not connect with Authentication=ActiveDirectoryIntegrated.
Array
(
    [0] => IMSSP
    [SQLSTATE] => IMSSP
    [1] => -62
    [code] => -62
    [2] => Invalid option for the Authentication keyword. Only SqlPassword or ActiveDirectoryPassword is supported.
    [message] => Invalid option for the Authentication keyword. Only SqlPassword or ActiveDirectoryPassword is supported.
)
%s with Authentication=ActiveDirectoryPassword.
