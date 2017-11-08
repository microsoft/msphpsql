--TEST--
Test PDOStatement::closeCursor method.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

try {
    $db = connect();
    $tbname = "PDO_AllTypes";
    createAndInsertTableAllTypes($db, $tbname);
    $stmt = $db->prepare("SELECT * FROM $tbname");
    $stmt->execute();
    $result = $stmt->fetch();

    dropTable($db, $tbname);
    unset($stmt);
    unset($db);
    echo "Test complete!\n";
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Test complete!
