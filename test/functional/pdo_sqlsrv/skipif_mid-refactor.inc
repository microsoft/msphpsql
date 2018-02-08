<?php
if (!extension_loaded("pdo") || !extension_loaded('pdo_sqlsrv')) {
    die("PDO driver cannot be loaded; skipping test.\n");
}

require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

$dsn = getDSN($server, null);
$conn = new PDO($dsn, $uid, $pwd);
if (! $conn) {
    echo("Error: could not connect during SKIPIF!");
} elseif (isColEncrypted()) {
    if (!(strtoupper(substr(PHP_OS, 0, 3)) === 'WIN')) {
        die( "Skip, AE test on windows only." );
    }

    if (!isAEQualified($conn)) {
        die("skip - AE feature not supported in the current environment.");
    }
}
