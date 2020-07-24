<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    die("skip: Test for Linux and macOS");
}

if (!extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

require_once('MsSetup.inc');
if ($localeDisabled) {
    die("skip not set up to test ansi locale");
}

// $loc = setlocale(LC_ALL, 'fr_FR@euro');
// $loc1 = setlocale(LC_ALL, 'fr_FR.ISO8859-15');
// if (empty($loc) && empty($loc1)) {
    // die("skip required French locale not available");
// }

?>