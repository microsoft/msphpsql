<?php

if (! extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

require_once('MsCommon.inc');

$conn = AE\connect();
if (! $conn) {
    die("Skip Could not connect during SKIPIF!");
} elseif (AE\isColEncrypted()) {
    if (!AE\isQualified($conn)) {
        die("skip AE feature not supported in the current environment.");
    }
}
