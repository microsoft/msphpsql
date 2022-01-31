--TEST--
Test ComputePool keyword
--DESCRIPTION--
This test does not test if any connection is successful but mainly test if the computepool keyword is recognized.
--SKIPIF--
<?php require('skipif.inc');?>
--FILE--
<?php
require_once 'MsSetup.inc';

$connectionOptions = array('ComputePool' => 'pool1');
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn != false) {
    sqlsrv_close($conn);
}

echo 'Done' . PHP_EOL;

?>
--EXPECT--
Done
