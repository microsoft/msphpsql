<?php
// Check if the Azure AD Access Token test should be skipped 
// From online documentation - For the ODBC Driver version 13.1, 
// the Azure Active Directory access token authentication is Windows only.

if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");

$conn = new PDO("sqlsrv:server = $server; driver=$driver;", $uid, $pwd);
if ($conn === false) {
    die("skip Could not connect during SKIPIF.");
}

$msodbcsqlVer = $conn->getAttribute(PDO::ATTR_CLIENT_VERSION)["DriverVer"];
$msodbcsqlMaj = explode(".", $msodbcsqlVer)[0];

$isWin = (strtoupper(substr(php_uname('s'), 0, 3)) === 'WIN');
if (!$isWin && $msodbcsqlMaj < 17) {
    die("skip: Unsupported ODBC driver version");
}

// Now check SQL Server version - exclude this check if running on Azure
if (!$daasMode) {
    $stmt = $conn->query("SELECT @@VERSION");
    if ($stmt) {
        $ver_string = $stmt->fetch(PDO::FETCH_NUM)[0];
    } else {
        die("skip Could not fetch SQL Server version during SKIPIF.");
    }

    $version = explode(' ', $ver_string);

    if ($version[3] < '2016') {
        die("skip: Wrong version of SQL Server, 2016 or later required");
    }
}

?>
