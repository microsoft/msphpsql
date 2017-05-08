--TEST--
Test the Authentication keyword with options SqlPassword and ActiveDirectoryIntegrated.
--SKIPIF--

--FILE--
<?php
require_once("autonomous_setup.php");

$connectionInfo = " Authentication = SqlPassword; TrustServerCertificate = true;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; $connectionInfo", $username, $password );
    echo "Connected successfully with Authentication=SqlPassword.\n";
}
catch( PDOException $e )
{
    echo "Could not connect with Authentication=SqlPassword.\n";
    print_r( $e->getMessage() );
    echo "\n";
}

$stmt = $conn->query( "SELECT name FROM master.dbo.sysdatabases" );
if ( $stmt === false )
{
    echo "Query failed.\n";
}
else
{
    $first_db = $stmt->fetch();
    var_dump( $first_db );
}

$conn = null;

////////////////////////////////////////

$connectionInfo = "Authentication = ActiveDirectoryIntegrated; TrustServerCertificate = true;";

try
{
    $conn = new PDO( "sqlsrv:server = $serverName ; $connectionInfo" );
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
  ["name"]=>
  string(6) "master"
  [0]=>
  string(6) "master"
}
Could not connect with Authentication=ActiveDirectoryIntegrated.
SQLSTATE[IMSSP]: Invalid option for the Authentication keyword. Only SqlPassword or ActiveDirectoryPassword is supported.