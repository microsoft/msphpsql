<?php
// For connection resiliency, must check that 
// 1. Either running Windows, or ODBC driver 17.2 or greater on unix
// 2. Either SQL Server version is 2014 or greater, or using Azure

if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

$is_win = ( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' );

require_once( "MsSetup.inc" );

$conn = new PDO( "sqlsrv:server = $server ;", $uid, $pwd );
if ($conn === false) {
    die( "skip Could not connect during SKIPIF." );
}

$msodbcsql_ver = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)["DriverVer"];
$msodbcsql_maj = explode(".", $msodbcsql_ver)[0];
$msodbcsql_min = explode(".", $msodbcsql_ver)[1];

if (!$is_win) {
    if ($msodbcsql_maj < 17 or $msodbcsql_min < 2) {
        die("skip Unsupported ODBC driver version");
    }
}

// Get SQL Server Version
// Exclude this check if running on Azure
if (!$daasMode) {
    $stmt = $conn->query( "SELECT @@VERSION" );
    if ($stmt) {
        $ver_string = $stmt->fetch(PDO::FETCH_NUM)[0];
    } else {
        die( "skip Could not fetch SQL Server version during SKIPIF.");
    }

    $version = explode(' ', $ver_string);

    if ($version[3] < '2014') {
        die("skip Wrong version of SQL Server, 2014 or later required");
    }
}
?>
