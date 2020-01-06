<?php

if (! extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

require_once("MsSetup.inc");
if ($keystore != 'akv') {
    die('skip the test requires valid Azure Key Vault credentials.');
}

if ($driver != "ODBC Driver 17 for SQL Server") {
    // the testing is not set to use ODBC 17
    die("skip AE feature not supported in the current environment.");
}

require_once('MsCommon.inc');

$conn = AE\connect();
if (! $conn) {
    die("skip could not connect during SKIPIF!");
} elseif (!AE\isQualified($conn)) {
    die("skip AE feature not supported in the current environment.");
}
