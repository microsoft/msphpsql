<?php

if (! extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

require_once('MsCommon.inc');

$conn = AE\connect();
if (! $conn) {
    echo("Error: could not connect during SKIPIF!");
} elseif (AE\isColEncrypted()) {
    if (!isWindows()) {
        die( "Skip, AE test on windows only." );
    }

    if (!AE\isQualified($conn)) {
        die("skip - AE feature not supported in the current environment.");
    }
}
?>