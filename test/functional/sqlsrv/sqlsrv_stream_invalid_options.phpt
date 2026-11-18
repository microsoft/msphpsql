--TEST--
Stream opener rejects unsupported options with a warning, with and without a context
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
$warnings = array();
set_error_handler(function ($severity, $message) use (&$warnings) {
    $warnings[] = $severity === E_WARNING
        && strpos($message, 'Invalid option: no options except REPORT_ERRORS may be specified with a sqlsrv stream') !== false;
    return true;
});
try {
    var_dump(fopen('sqlsrv://invalid', 'rb', true));
    var_dump(fopen('sqlsrv://invalid', 'rb', true, stream_context_create()));
} finally {
    restore_error_handler();
}
var_dump($warnings);
?>
--EXPECT--
bool(false)
bool(false)
array(2) {
  [0]=>
  bool(true)
  [1]=>
  bool(true)
}
