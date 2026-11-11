--TEST--
PDO UTF-8 command conversion rejects malformed input with a deterministic diagnostic
--DESCRIPTION--
Runs without a legacy locale on all supported platforms. Covers default and
explicit UTF-8, preparation, execution, emulated binding and valid-input recovery.
Uses only table-free SELECT statements against the configured test database.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('MsCommon.inc');
require(__DIR__ . '/../inc/command_text_encoding.inc');
$legacy = null;
require('command_text_encoding.inc');
?>
--EXPECT--
default UTF-8: passed
explicit UTF-8: passed
