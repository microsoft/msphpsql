--TEST--
Test the Authentication keyword with options SqlPassword and ActiveDirectoryIntegrated.
--SKIPIF--

--FILE--
<?php
require_once("MsSetup.inc");

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
    $first_db = sqlsrv_fetch_array( $stmt );
    var_dump( $first_db );
}

sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );

////////////////////////////////////////

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

?>
--EXPECT--
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