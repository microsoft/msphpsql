--TEST--
Test the Authentication keyword and its accepted values: SqlPassword and ActiveDirectoryPassword.
--SKIPIF--

--FILE--
<?php
require_once("autonomous_setup.php");

$connectionInfo = array( "UID"=>$username, "PWD"=>$password,
                         "Authentication"=>'SqlPassword', "TrustServerCertificate"=>true );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect with Authentication=SqlPassword.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully with Authentication=SqlPassword.\n";
}

$stmt = sqlsrv_query( $conn, "SELECT name FROM master.dbo.sysdatabases" );
if ( $stmt === false )
{
    echo "Query failed.\n";
}
else
{
    $first_db = sqlsrv_fetch_array( $stmt );
    var_dump( $first_db );
}

sqlsrv_close( $conn );

?>
--EXPECT--
Connected successfully with Authentication=SqlPassword.
array(2) {
  ["name"]=>
  string(6) "master"
  [0]=>
  string(6) "master"
}