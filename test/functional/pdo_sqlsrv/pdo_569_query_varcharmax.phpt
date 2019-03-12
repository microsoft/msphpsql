--TEST--
GitHub issue #569 - direct query on varchar max fields results in function sequence error
--DESCRIPTION--
Verifies that the problem is no longer reproducible.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

try {
    $connectionInfo = "ColumnEncryption = Enabled;";
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::SQLSRV_ATTR_DIRECT_QUERY, true);

    $tableName = 'pdoTestTable_569';
    $colMetaArr = array(new ColumnMeta('varchar(max)', 'col1'));
    createTable($conn, $tableName, $colMetaArr);

    $input = 'some very large string';
    $stmt = insertRow($conn, $tableName, array('col1' => $input));

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
    if ($rows != 1) {
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
    if ($result) {
        echo 'Expected bool(false) when fetching an empty table but got: ';
        var_dump($result);
    }

    // Fetch the same table but using client cursor
    $stmt = $conn->prepare($tsql, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
    $stmt->execute();

    $result = $stmt->fetch();
    if ($result) {
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