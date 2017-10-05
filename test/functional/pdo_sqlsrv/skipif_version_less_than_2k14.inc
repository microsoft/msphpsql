<?php
if ( !( strtoupper( substr( php_uname( 's' ),0,3 ) ) === 'WIN' ) ) die( "Skip Test on windows only." ); 

if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once( "MsSetup.inc" );

$conn = new PDO( "sqlsrv:server = $server ;", $uid, $pwd );
if ($conn === false) {
    die( "skip Could not connect during SKIPIF." );
}

// Get SQL Server Version
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
?>
