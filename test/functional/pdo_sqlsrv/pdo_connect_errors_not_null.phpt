--TEST--
GitHub Issue #1514 - PDO connection failure must always populate errorInfo
--DESCRIPTION--
When a PDO connection fails, the PDOException must always contain meaningful
errorInfo. This is the pdo_sqlsrv counterpart of the sqlsrv fix ensuring ODBC
failures without diagnostic records still produce a usable error message.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

// Test 1: Connection to a non-existent server
try {
    $conn = new PDO("sqlsrv:Server=nonexistent_server_12345;LoginTimeout=1;Encrypt=no", "", "");
    echo "Test 1 FAILED: Expected connection to fail\n";
} catch (PDOException $e) {
    $info = $e->errorInfo;
    if (!is_array($info) || count($info) < 3) {
        echo "Test 1 FAILED: errorInfo missing expected fields\n";
    } elseif (empty($info[0]) || empty($info[2])) {
        echo "Test 1 FAILED: errorInfo has empty SQLSTATE or message\n";
    } else {
        echo "Test 1 passed: PDOException has proper errorInfo\n";
    }
}

// Test 2: Connection with invalid credentials
require_once('MsSetup.inc');
try {
    $conn = new PDO("sqlsrv:Server=$server;Database=$databaseName;Driver=$driver;Encrypt=$encrypt;LoginTimeout=5", "bogus_user_12345", "bogus_pwd");
    // If the connection succeeded (e.g., Windows auth fallback), skip
    echo "Test 2 passed: connection succeeded (Windows auth or trusted)\n";
} catch (PDOException $e) {
    $info = $e->errorInfo;
    if (!is_array($info) || count($info) < 3) {
        echo "Test 2 FAILED: errorInfo missing expected fields\n";
    } elseif (empty($info[0]) || empty($info[2])) {
        echo "Test 2 FAILED: errorInfo has empty SQLSTATE or message\n";
    } else {
        echo "Test 2 passed: PDOException has proper errorInfo\n";
    }
}

echo "Done\n";

?>
--EXPECT--
Test 1 passed: PDOException has proper errorInfo
Test 2 passed: PDOException has proper errorInfo
Done
