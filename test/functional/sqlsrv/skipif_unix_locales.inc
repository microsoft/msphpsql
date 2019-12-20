<?php

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    die("skip: Test for Linux and macOS");
}

if (!extension_loaded("sqlsrv")) {
    die("skip extension not loaded");
}

// check if the required ini file exists
$inifile = PHP_CONFIG_FILE_SCAN_DIR."/99-overrides.ini";
if (!file_exists($inifile)) {
    die("skip required ini file not exists");
}

// if the file exists, is it writable? '@' sign is used to suppress warnings
$file = @fopen($inifile, "w");
if (!$file) {
    die("skip required ini file not writable");
}

fclose($file);

$loc = setlocale(LC_TIME, 'de_DE.UTF-8');
if (empty($loc)) {
    die("skip required locale not available");
}
