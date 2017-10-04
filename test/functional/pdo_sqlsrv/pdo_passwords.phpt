--TEST--
Test password with non alphanumeric characters
--DESCRIPTION--
The first three cases should have no problem connecting. Only the last case fails because the
right curly brace should be escaped with another right brace.
In Azure for this test to pass do not specify any particular database when connecting
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

$dsn = "sqlsrv:Server=$server;ConnectionPooling=false;";
// get DSN information for Column Encryption
if ($keystore != "none") {
    $dsn .= "ColumnEncryption=Enabled;";
}
if ($keystore == "ksp") {
    require('AE_Ksp.inc');
    $ksp_path = getKSPPath();
    $dsn .= "CEKeystoreProvider=$ksp_path;CEKeystoreName=$ksp_name;CEKeystoreEncryptKey=$encrypt_key;";
}

try {
    // Test 1
    $conn = new PDO($dsn, "test_password", "! ;4triou");
    if (!$conn) {
        echo "Test 1: Should have connected.";
    }
    unset($conn);
} catch (PDOException $e) {
    print_r($e->getMessage() . "\n");
}
try {
    // Test 2
    $conn = new PDO($dsn, "test_password2", "!}} ;4triou");
    if (!$conn) {
        echo "Test 2: Should have connected.";
    }
    unset($conn);
} catch (PDOException $e) {
    print_r($e->getMessage() . "\n");
}
try {
    // Test 3
    $conn = new PDO($dsn, "test_password3", "! ;4triou}}");
    if (!$conn) {
        echo "Test 3: Should have connected.";
    }
    unset($conn);
} catch (PDOException $e) {
    print_r($e->getMessage() . "\n");
}
// Test invalid password.
try {
    // Test 4
    $conn = new PDO($dsn, "test_password3", "! ;4triou}");
} catch (PDOException $e) {
    print_r($e->getMessage());
    exit;
}

?>

--EXPECTREGEX--
SQLSTATE\[IMSSP\]: An unescaped right brace \(}\) was found in either the user name or password\.  All right braces must be escaped with another right brace \(}}\)\.
