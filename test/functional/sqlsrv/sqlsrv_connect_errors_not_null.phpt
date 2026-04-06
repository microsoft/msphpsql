--TEST--
GitHub Issue #1514 - sqlsrv_connect must always populate sqlsrv_errors() on failure
--DESCRIPTION--
When sqlsrv_connect returns false, sqlsrv_errors() should never return null.
Previously, if the ODBC driver returned SQL_ERROR without setting a diagnostic
record (e.g. connecting to Microsoft Fabric Data Warehouse with MARS enabled),
sqlsrv_errors() would return null, making the failure impossible to diagnose.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', 0);

// Test 1: Connection to a non-existent server should produce errors
$conn = sqlsrv_connect("nonexistent_server_12345", array("LoginTimeout" => 1, "Encrypt" => "no"));
if ($conn !== false) {
    fatalError("Test 1: Expected connection to fail");
}
$errors = sqlsrv_errors();
if ($errors === null) {
    echo "Test 1 FAILED: sqlsrv_errors() returned null after failed connection\n";
} else {
    // Verify error structure
    if (!is_array($errors) || count($errors) === 0) {
        echo "Test 1 FAILED: sqlsrv_errors() returned empty array\n";
    } elseif (!isset($errors[0][0]) || !isset($errors[0][1]) || !isset($errors[0][2])) {
        echo "Test 1 FAILED: error entry missing expected fields\n";
    } else {
        echo "Test 1 passed: sqlsrv_errors() returned proper error info\n";
    }
}

// Test 2: Connection with invalid driver should produce errors
$conn = sqlsrv_connect("localhost", array("Driver" => "Nonexistent Driver 99", "LoginTimeout" => 1, "Encrypt" => "no"));
if ($conn !== false) {
    fatalError("Test 2: Expected connection to fail");
}
$errors = sqlsrv_errors();
if ($errors === null) {
    echo "Test 2 FAILED: sqlsrv_errors() returned null after failed connection\n";
} else {
    echo "Test 2 passed: sqlsrv_errors() returned proper error info\n";
}

// Test 3: Connection with invalid credentials should produce errors
require_once('MsSetup.inc');
$conn = sqlsrv_connect($server, array("UID" => "bogus_user_12345", "PWD" => "bogus_pwd", "LoginTimeout" => 5, "Driver" => $driver, "Encrypt" => $encrypt));
if ($conn !== false) {
    // If the connection succeeded (e.g., Windows auth fallback), just skip this check
    echo "Test 3 passed: connection succeeded (Windows auth or trusted)\n";
    sqlsrv_close($conn);
} else {
    $errors = sqlsrv_errors();
    if ($errors === null) {
        echo "Test 3 FAILED: sqlsrv_errors() returned null after failed connection\n";
    } else {
        echo "Test 3 passed: sqlsrv_errors() returned proper error info\n";
    }
}

echo "Done\n";

?>
--EXPECT--
Test 1 passed: sqlsrv_errors() returned proper error info
Test 2 passed: sqlsrv_errors() returned proper error info
Test 3 passed: sqlsrv_errors() returned proper error info
Done
