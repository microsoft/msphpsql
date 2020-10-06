<?php
// For AE v2, need ODBC driver 17.4 or above, an enclave enabled
// SQL Server, and a HGS server. The HGS server and SQL Server
// are the same for testing purposes.

if (!extension_loaded("sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");

if ($attestation == 'TARGET_ATTESTATION') {
    die("skip Not set up for testing with secure enclave.");
}

$connectionInfo = array("UID"=>$userName, "PWD"=>$userPassword);

$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === false) {
    die("skip Could not connect during SKIPIF.");
}

$msodbcsql_ver = sqlsrv_client_info($conn)["DriverVer"];
$msodbcsql_maj = explode(".", $msodbcsql_ver)[0];
$msodbcsql_min = explode(".", $msodbcsql_ver)[1];

if ($msodbcsql_maj < 17) {
    die("skip Unsupported ODBC driver version");
}

if ($msodbcsql_min < 4 and $msodbcsql_maj == 17) {
    die("skip Unsupported ODBC driver version");
}

// Get SQL Server
$server_info = sqlsrv_server_info($conn);
if (strpos($server_info['SQLServerName'], 'PHPHGS') === false) {
    die("skip Server is not HGS enabled");
}
?>
