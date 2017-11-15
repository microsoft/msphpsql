<?php

if (!extension_loaded("sqlsrv")) 
    die("skip extension not loaded");

require_once('MsCommon.inc');

if ($keystore != AE\KEYSTORE_KSP)
    die('skip - this test requires a custom keystore provider.');

$conn = AE\connect();
if (! $conn) {
    echo("Error: could not connect during SKIPIF!");
} elseif (AE\isColEncrypted() && !AE\isQualified($conn)) {
    die("skip - AE feature not supported in the current environment.");
}
?>