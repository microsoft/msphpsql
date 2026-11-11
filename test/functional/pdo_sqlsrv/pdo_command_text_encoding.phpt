--TEST--
PDO legacy command text uses strict conversion for execution and preparation
--DESCRIPTION--
Requires Linux and an installed GBK/GB2312, Big5 or CP932 locale. The current LC_CTYPE
is preferred, so separate test processes can exercise each installed locale.
Uses only table-free SELECT statements against the configured test database.
--SKIPIF--
<?php
require('skipif_unix_ansitests.inc');
require(__DIR__ . '/../inc/command_text_encoding.inc');
if (commandTextLocale() === false) {
    die('skip requires Linux with an installed GBK/GB2312, Big5 or CP932 locale');
}
?>
--FILE--
<?php
require('MsCommon.inc');
require(__DIR__ . '/../inc/command_text_encoding.inc');
$legacy = commandTextLocale();
checkCommandText($legacy !== false, 'Legacy locale unavailable');
require('command_text_encoding.inc');
?>
--EXPECT--
SYSTEM: passed
Statement encoding override: passed
