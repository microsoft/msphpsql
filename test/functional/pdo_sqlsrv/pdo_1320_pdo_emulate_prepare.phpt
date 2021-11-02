--TEST--
GitHub issue 1320 - support PDO::ATTR_EMULATE_PREPARES at the connection level
--DESCRIPTION--
Supports PDO::ATTR_EMULATE_PREPARES at the connection level but setting it to true with column
encryption enabled will fail with an exception. Also, the options in the prepared statement will
override the connection setting.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

try {
    // Connection with column encryption enabled
    $connectionInfo = "ColumnEncryption = Enabled;";
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    $conn->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
    echo "setAttribute should have failed because column encryption is enabled.\n\n";
} catch (PDOException $e) {
    echo $e->getMessage() . "\n";
}

unset($conn);

try {
    // Connection with column encryption enabled
    $options = array(PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_EMULATE_PREPARES => true);
    $connectionInfo = "ColumnEncryption = Enabled;";
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd, $options);
} catch (PDOException $e) {
    echo $e->getMessage() . "\n";
}

unset($conn);

try {
    // Connection with column encryption enabled - PDO::ATTR_EMULATE_PREPARES is false by default
    $connectionInfo = "ColumnEncryption = Enabled;";
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    echo "Connected successfully with column encryption enabled.\n";
} catch (PDOException $e) {
    echo $e->getMessage() . "\n";
}

?> 

--EXPECT--
SQLSTATE[IMSSP]: Parameterized statement with attribute PDO::ATTR_EMULATE_PREPARES is not supported in a Column Encryption enabled Connection.
SQLSTATE[IMSSP]: Parameterized statement with attribute PDO::ATTR_EMULATE_PREPARES is not supported in a Column Encryption enabled Connection.
Connected successfully with column encryption enabled.