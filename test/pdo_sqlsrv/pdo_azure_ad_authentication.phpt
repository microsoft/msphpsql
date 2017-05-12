--TEST--
Test the Authentication keyword with options SqlPassword and ActiveDirectoryIntegrated.
--SKIPIF--

--FILE--
<?php
require_once("MsSetup.inc");

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

////////////////////////////////////////

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

?>
--EXPECT--
Connected successfully with Authentication=SqlPassword.
array(2) {
  [""]=>
  string(1) "7"
  [0]=>
  string(1) "7"
}
Could not connect with Authentication=ActiveDirectoryIntegrated.
SQLSTATE[IMSSP]: Invalid option for the Authentication keyword. Only SqlPassword or ActiveDirectoryPassword is supported.