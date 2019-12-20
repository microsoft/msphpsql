<?php
if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

$dsn = getDSN($server, null);
$conn = new PDO($dsn, $uid, $pwd);
if (! $conn) {
    die("skip could not connect during SKIPIF!");
} elseif (isColEncrypted()) {
    if (!isAEQualified($conn)) {
        die("skip - AE feature not supported in the current environment.");
    }
}
