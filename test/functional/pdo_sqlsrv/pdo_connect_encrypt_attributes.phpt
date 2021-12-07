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

try {
    $encrypt = ' true ';
    $trust = 'true ';
    $connectionInfo = "Database = $databaseName; Encrypt = $encrypt; TrustServerCertificate =  $trust;";
    $conn1 = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    echo 'Test case 1' . PHP_EOL;
} catch (PDOException $e) {
    echo 'Failed to connect (test case 1)' . PHP_EOL;
    print_r($e->getMessage());
    echo PHP_EOL;
}

unset($conn1);

try {
    $encrypt = ' 1 ';
    $trust = 'true ';
    $connectionInfo = "Database = $databaseName; Encrypt = $encrypt; TrustServerCertificate =  $trust;";
    $conn2 = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    echo 'Test case 2' . PHP_EOL;
} catch (PDOException $e) {
    echo 'Failed to connect (test case 2)' . PHP_EOL;
    print_r($e->getMessage());
    echo PHP_EOL;
}

unset($conn2);

try {
    $encrypt = '  yes  ';
    $trust = 'true';
    $connectionInfo = "Database = $databaseName; Encrypt = $encrypt; TrustServerCertificate =  $trust;";
    $conn3 = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    echo 'Test case 3' . PHP_EOL;
} catch (PDOException $e) {
    echo 'Failed to connect (test case 3)' . PHP_EOL;
    print_r($e->getMessage());
    echo PHP_EOL;
}

unset($conn3);

try {
    $encrypt = ' 0 ';
    $trust = 'false ';
    $connectionInfo = "Database = $databaseName; Encrypt = $encrypt; TrustServerCertificate =  $trust;";
    $conn4 = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    echo 'Test case 4' . PHP_EOL;
} catch (PDOException $e) {
    echo 'Failed to connect (test case 4)' . PHP_EOL;
    print_r($e->getMessage());
    echo PHP_EOL;
}

unset($conn4);

try {
    $encrypt = ' false  ';
    $trust = 'false ';
    $connectionInfo = "Database = $databaseName; Encrypt = $encrypt; TrustServerCertificate =  $trust;";
    $conn5 = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    echo 'Test case 5' . PHP_EOL;
} catch (PDOException $e) {
    echo 'Failed to connect (test case 5)' . PHP_EOL;
    print_r($e->getMessage());
    echo PHP_EOL;
}

unset($conn5);

try {
    $encrypt = 'no  ';
    $trust = 'false ';
    $connectionInfo = "Database = $databaseName; Encrypt = $encrypt; TrustServerCertificate =  $trust;";
    $conn6 = new PDO("sqlsrv:server = $server ; $connectionInfo", $uid, $pwd);
    echo 'Test case 6' . PHP_EOL;
} catch (PDOException $e) {
    echo 'Failed to connect (test case 6)' . PHP_EOL;
    print_r($e->getMessage());
    echo PHP_EOL;
}

unset($conn6);
echo 'Done' . PHP_EOL;

?>
--EXPECT--
Test case 1
Test case 2
Test case 3
Test case 4
Test case 5
Test case 6
Done
