<?php
// For Azure ActiveDirectory, must check that 
// 1. Either running Windows, or ODBC driver 17 or greater on unix
// 2. Either SQL Server version is 2016 or greater, or using Azure

if (!extension_loaded("sqlsrv")) {
    die("skip Extension not loaded");
}

$is_win = ( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' );

require_once( "MsSetup.inc" );

$connectionInfo = array( "UID"=>$userName, "PWD"=>$userPassword );

$conn = sqlsrv_connect( $server, $connectionInfo );
if ($conn === false) {
    die( "skip Could not connect during SKIPIF." );
}

$msodbcsql_ver = sqlsrv_client_info($conn)["DriverVer"];
$msodbcsql_maj = explode(".", $msodbcsql_ver)[0];

if (!$is_win) {
    if ($msodbcsql_maj < 17) {
        die("skip Unsupported ODBC driver version");
    }
}

// Get SQL Server version
// Exclude this check if running on Azure
if (!$daasMode) {
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
}
?>
