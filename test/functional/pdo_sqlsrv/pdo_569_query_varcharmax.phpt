--TEST--
GitHub issue #569 - direct query on varchar max fields results in function sequence error
--DESCRIPTION--
Verifies that the problem is no longer reproducible.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

try {
    // This test requires to connect with the Always Encrypted feature
    // First check if the system is qualified to run this test
    $dsn = getDSN($server, null);
    $conn = new PDO($dsn, $uid, $pwd);
    $qualified = isAEQualified($conn);
    
    if ($qualified) {
        unset($conn);

        // Now connect with ColumnEncryption enabled
        $connectionInfo = "ColumnEncryption = Enabled;";
        $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd);
    }

    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tableName = 'pdoTestTable_569';
    dropTable($conn, $tableName);

    if ($qualified && strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
        $tsql = "CREATE TABLE $tableName ([c1] varchar(max) COLLATE Latin1_General_BIN2 ENCRYPTED WITH (ENCRYPTION_TYPE = deterministic, ALGORITHM = 'AEAD_AES_256_CBC_HMAC_SHA_256', COLUMN_ENCRYPTION_KEY = AEColumnKey))";
    } else {
        $tsql = "CREATE TABLE $tableName ([c1] varchar(max))";
    }
    $conn->exec($tsql);

    $input = 'some very large string';
    $tsql = "INSERT INTO $tableName (c1) VALUES (?)";
    $stmt = $conn->prepare($tsql);
    $param = array($input);
    $stmt->execute($param);
    
    $tsql = "SELECT * FROM $tableName";
    try {
        $stmt = $conn->prepare($tsql);
        $stmt->execute();
    } catch (PDOException $e) {
        echo ("Failed to read from $tableName\n");
        echo $e->getMessage();
    }

    $row = $stmt->fetch(PDO::FETCH_NUM);
    if ($row[0] !== $input) {
        echo "Expected $input but got: ";
        var_dump($row[0]);
    }

    $tsql2 = "DELETE FROM $tableName";
    $rows = $conn->exec($tsql2);
    if ($rows !== 1) {
        echo 'Expected 1 row affected but got: ';
        var_dump($rows);
    }
    
    // Fetch from the empty table
    try {
        $stmt = $conn->prepare($tsql);
        $stmt->execute();
    } catch (PDOException $e) {
        echo ("Failed to read $tableName, now empty\n");
        echo $e->getMessage();
    }

    $result = $stmt->fetch(PDO::FETCH_NUM);
    if ($result !== false) {
        echo 'Expected bool(false) when fetching an empty table but got: ';
        var_dump($result);
    }

    // Fetch the same table but using client cursor
    $stmt = $conn->prepare($tsql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
    $stmt->execute();

    $result = $stmt->fetch();
    if ($result !== false) {
        echo 'Expected bool(false) when fetching an empty table but got: ';
        var_dump($result);
    }
    
    dropTable($conn, $tableName);

    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}

echo "Done\n";

?>
--EXPECT--
Done