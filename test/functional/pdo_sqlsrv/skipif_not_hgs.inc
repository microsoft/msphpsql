<?php
// For AE v2, need ODBC driver 17.4 or above, an enclave enabled
// SQL Server, and a HGS server. The HGS server and SQL Server
// are the same for testing purposes.

if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once('MsSetup.inc');

if ($attestation == 'TARGET_ATTESTATION') {
    die("skip Not set up for testing with secure enclave.");
}

$conn = new PDO("sqlsrv:server = $server", $uid, $pwd);
if (!$conn) {
    die("skip Could not connect during SKIPIF.");
}

$msodbcsqlVer = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)['DriverVer'];
$version = explode(".", $msodbcsqlVer);

$msodbcsqlMaj = $version[0];
$msodbcsqlMin = $version[1];

if ($msodbcsqlMaj < 17) {
    die("skip Unsupported ODBC driver version");
}

if ($msodbcsqlMin < 4 and $msodbcsqlMaj == 17) {
    die("skip Unsupported ODBC driver version");
}

// Get SQL Server
$serverInfo = $conn->getAttribute(PDO::ATTR_SERVER_INFO);
if (strpos($serverInfo['SQLServerName'], 'PHPHGS') === false) {
    die("skip Server is not HGS enabled");
}
?>