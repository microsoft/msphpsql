<?php

if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once("MsSetup.inc");
require_once("MsCommon.inc");

$conn = ae_connect();
if (! $conn) {
    die("skip could not connect during SKIPIF!");
} elseif (! IsAEQualified($conn)) {
    die("skip AE feature not supported in the current environment.");
}
