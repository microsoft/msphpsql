--TEST--
Test the Authentication keyword with options SqlPassword and ActiveDirectoryIntegrated.
--SKIPIF--

--FILE--
<?php
require_once("autonomous_setup.php");

//if (strtoupper(substr(PHP_OS, 0, 3)) != 'WIN')
{
    $connectionInfo = array( "UID"=>$username, "PWD"=>$password,
                             "Authentication"=>"SqlPassword", "TrustServerCertificate"=>true );

    $conn = sqlsrv_connect( $serverName, $connectionInfo );

    if( $conn === false )
    {
        echo "Could not connect with Authentication=SqlPassword.\n";
        print_r( sqlsrv_errors() );
    }
    // else
    // {
        // echo "Connected successfully with Authentication=SqlPassword.\n";
    // }

    $stmt = sqlsrv_query( $conn, "SELECT name FROM master.dbo.sysdatabases" );
    if ( $stmt === false )
    {
        echo "Query failed.\n";
    }
    // else
    // {
        // $first_db = sqlsrv_fetch_array( $stmt );
        // var_dump( $first_db );
    // }

    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );
}

////////////////////////////////////////

$connectionInfo = array( "Authentication"=>"ActiveDirectoryIntegrated", "TrustServerCertificate"=>true );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( $conn === false )
{
    echo "Could not connect with Authentication=ActiveDirectoryIntegrated.\n";
    print_r( sqlsrv_errors() );
}
else
{
    echo "Connected successfully with Authentication=ActiveDirectoryIntegrated.\n";
    sqlsrv_close( $conn );
}

?>
--EXPECT--
Could not connect with Authentication=ActiveDirectoryIntegrated.
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -62
            [code] => -62
            [2] => Invalid option for the Authentication keyword. Only SqlPassword or ActiveDirectoryPassword is supported.
            [message] => Invalid option for the Authentication keyword. Only SqlPassword or ActiveDirectoryPassword is supported.
        )

)
