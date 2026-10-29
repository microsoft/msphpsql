<?php
require('skipif.inc');
require_once('MsCommon.inc');

// These fixtures contain single-byte text, not UTF-8.
if (!isWindows()) {
    if (isLocaleDisabled()) {
        die('skip ANSI fixture locale is disabled');
    }
    setUSAnsiLocale();
    if (!preg_match('/^en_US\.ISO-?8859-1$/i', setlocale(LC_CTYPE, 0))) {
        die('skip requires the en_US ISO-8859-1 locale for ANSI fixtures');
    }
}
