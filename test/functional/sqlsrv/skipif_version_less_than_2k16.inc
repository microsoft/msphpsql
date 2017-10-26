<?php
if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip Test on windows only." ); 

if (!extension_loaded("sqlsrv")) {
    die("skip Extension not loaded");
}

require_once( "MsSetup.inc" );

$connectionInfo = array( "UID"=>$userName, "PWD"=>$userPassword );

$conn = sqlsrv_connect( $server, $connectionInfo );
if ($conn === false) {
    die( "skip Could not connect during SKIPIF." );
}

// Get SQL Server version
$stmt = sqlsrv_query( $conn, "SELECT @@VERSION" );
if (sqlsrv_fetch($stmt)) {
    $ver_string = sqlsrv_get_field( $stmt, 0 );
} else {
    die("skip Could not fetch SQL Server version.");
}

$version = explode(' ', $ver_string);

if ($version[3] < '2016') {
    die("skip Wrong version of SQL Server, 2016 or later required");
}
?>
