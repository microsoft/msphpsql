--TEST--
SQLSRV UTF-8 command conversion rejects malformed input with a deterministic diagnostic
--DESCRIPTION--
Runs without a legacy locale on all supported platforms. Covers preparation,
execution, valid non-ASCII text, native UTF-8 parameters and binary parameters.
Uses only table-free SELECT statements against the configured test database.
--ENV--
PHPT_EXEC=true
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
UTF-8: passed
