<?php

if (! extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

require_once('MsCommon.inc');

$conn = AE\connect();
if (! $conn) {
    echo("Error: could not connect during SKIPIF!");
} elseif (AE\isColEncrypted() && !AE\isQualified($conn)) {
    die("skip - AE feature not supported in the current environment.");
}
} elseif (testAnsiData() && !isWindows() && !AE\isQualified($conn)) {
    die("skip - non-UTF8 not supported in the current environment.");
}
?>
