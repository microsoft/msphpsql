--TEST--
Test PDOStatement::errorInfo and PDOStatement::errorCode methods.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

try {
    $db = connect();
    $tbname = "PDO_MainTypes";
    createAndInsertTableMainTypes($db, $tbname);
    $stmt = $db->prepare("SELECT * FROM $tbname");
    $stmt->execute();
    $arr = $stmt->errorInfo();
    print_r("Error Info :\n");
    var_dump($arr);
    $arr = $stmt->errorCode();
    print_r("Error Code : " . $arr . "\n");

    dropTable($db, $tbname);
    unset($stmt);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
Error Info :
array(3) {
  [0]=>
  string(5) "00000"
  [1]=>
  NULL
  [2]=>
  NULL
}
Error Code : 00000
