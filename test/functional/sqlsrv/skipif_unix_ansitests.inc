<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    die("skip: Test for Linux and macOS");
}

if (!extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

$loc = setlocale(LC_ALL, 'fr_FR@euro');
if (empty($loc)) {
    die("skip required French locale not available");
}

?>