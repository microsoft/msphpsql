<?php
if (!extension_loaded("pdo") || !extension_loaded('pdo_sqlsrv')) {
    die("PDO driver cannot be loaded; skipping test.\n");
}

require_once("MsSetup.inc");

if ($keystore != 'akv')
    die ( 'skip - the test requires valid Azure Key Vault credentials.' );
    
if ($driver != "ODBC Driver 17 for SQL Server") {
    // the testing is not set to use ODBC 17
    die("skip - AE feature not supported in the current environment.");
}

require_once("MsCommon_mid-refactor.inc");

$dsn = getDSN($server, null);
$conn = new PDO($dsn, $uid, $pwd);
if (! $conn) {
    echo("Error: could not connect during SKIPIF!");
} elseif (!isAEQualified($conn)) {
    die("skip - AE feature not supported in the current environment.");
}
