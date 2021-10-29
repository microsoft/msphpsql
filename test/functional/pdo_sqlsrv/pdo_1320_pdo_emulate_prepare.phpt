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

?> 

--EXPECT--
SQLSTATE[IMSSP]: The emulation of prepared statements is not supported when connecting with Column Encryption enabled.
SQLSTATE[IMSSP]: The emulation of prepared statements is not supported when connecting with Column Encryption enabled.