--TEST--
Test PDOStatement::columnCount if the number of the columns in a result set.
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
    $sql = "SELECT * FROM $tbname";
    $stmt = $db->prepare($sql);
    $stmt->execute();
    print_r("Existing table contains: " . $stmt->columnCount() . "\n");
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Existing table contains: 31
