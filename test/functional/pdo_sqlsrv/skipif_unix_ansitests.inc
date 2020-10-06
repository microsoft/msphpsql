<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    die("skip Test for Linux and macOS");
}

if (!extension_loaded("pdo_sqlsrv")) {
    die("skip Extension not loaded");
}

require_once('MsSetup.inc');
if ($localeDisabled) {
    die("skip not set up to test ansi locale");
}
?>
