--TEST--
Test various encrypt attributes
--DESCRIPTION--
This test does not test if any connection is successful but mainly test if the Encrypt keyword takes
different attributes.
--SKIPIF--
<?php require('skipif.inc');?>
--FILE--
<?php
require_once 'MsSetup.inc';

echo 'Test case 1' . PHP_EOL;
$connectionOptions = array('Encrypt' => true, 'TrustServerCertificate' => true);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn != false) {
    sqlsrv_close($conn);
}

echo 'Test case 2' . PHP_EOL;
$connectionOptions = array('Encrypt' => 1, 'TrustServerCertificate' => true);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn != false) {
    sqlsrv_close($conn);
}

echo 'Test case 3' . PHP_EOL;
$connectionOptions = array('Encrypt' => "yes", 'TrustServerCertificate' => true);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn != false) {
    sqlsrv_close($conn);
}

echo 'Test case 4' . PHP_EOL;
$connectionOptions = array('Encrypt' => "no");
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn != false) {
    sqlsrv_close($conn);
}

echo 'Test case 5' . PHP_EOL;
$connectionOptions = array('Encrypt' => false);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn != false) {
    sqlsrv_close($conn);
}

echo 'Test case 6' . PHP_EOL;
$connectionOptions = array('Encrypt' => 0);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn != false) {
    sqlsrv_close($conn);
}

echo 'Test case 7' . PHP_EOL;
$connectionOptions = array('Encrypt' => 3);
$conn = sqlsrv_connect($server, $connectionOptions);
if ($conn !== false) {
    echo 'Expect this to fail' . PHP_EOL;
}

echo 'Done' . PHP_EOL;

?>
--EXPECT--
Test case 1
Test case 2
Test case 3
Test case 4
Test case 5
Test case 6
Test case 7
Done
