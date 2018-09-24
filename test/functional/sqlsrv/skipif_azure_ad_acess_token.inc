<?php
// Check if the Azure AD Access Token test should be skipped 
// From online documentation - For the ODBC Driver version 13.1, 
// the Azure Active Directory access token authentication is Windows only.

if (!extension_loaded("sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");

$connectionInfo = array("UID"=>$userName, "PWD"=>$userPassword, "Driver" => $driver);

$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === false) {
    die("skip: Could not connect during SKIPIF.");
}

$msodbcsqlVer = sqlsrv_client_info($conn)['DriverVer'];
$msodbcsqlMaj = explode(".", $msodbcsqlVer)[0];

$isWin = (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN');

if (!$isWin && $msodbcsqlMaj < 17) {
    die("skip: Unsupported ODBC driver version");
}

// Now check SQL Server version - exclude this check if running on Azure
if (!$daasMode) {
    // Get SQL Server version
    $stmt = sqlsrv_query($conn, "SELECT @@VERSION");
    if (sqlsrv_fetch($stmt)) {
        $verString = sqlsrv_get_field($stmt, 0);
    } else {
        die("skip Could not fetch SQL Server version.");
    }
    
    $version = explode(' ', $verString);

    if ($version[3] < '2016') {
        die("skip: Wrong version of SQL Server, 2016 or later required");
    }
}

?>
