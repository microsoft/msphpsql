--TEST--
Test bindValue method.
--SKIPIF--
<?php require("skipif_mid-refactor.inc"); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

try {
    $db = connect();
    $tbname = "PDO_AllTypes";
    createAndInsertTableAllTypes($db, $tbname);
    $bigint = 1;
    $string = "STRINGCOL1";
    $stmt = $db->prepare("SELECT IntCol FROM $tbname WHERE BigIntCol = :bigint AND CharCol = :string");
    $stmt->bindValue(':bigint', $bigint, PDO::PARAM_INT);
    $stmt->bindValue(':string', $string, PDO::PARAM_STR);
    $stmt->execute();

    dropTable($db, $tbname);
    unset($stmt);
    unset($db);
    echo "Test Complete!\n";
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Test Complete!
