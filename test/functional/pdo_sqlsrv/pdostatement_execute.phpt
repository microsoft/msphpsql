--TEST--
Test PDOStatement::execute method.
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
    $rows = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($rows);

    // Test update table value
    if (isColEncrypted()) {
        $stmt = $db->prepare("UPDATE $tbname SET IntCol=?");
        $r = $stmt->execute(array(1));
    } else {
        $stmt = $db->prepare("UPDATE $tbname SET IntCol=1");
        $r = $stmt->execute();
    }
    var_dump($r);

    // Test insert value to table
    $inputs = array("IntCol" => 2,
                    "CharCol" => 'STRINGCOL1',
                    "NCharCol" => 'STRINGCOL1',
                    "DateTimeCol" => '2000-11-11 11:11:11.110',
                    "VarcharCol" => 'STRINGCOL1',
                    "NVarCharCol" => 'STRINGCOL1',
                    "FloatCol" => 111.111,
                    "XmlCol" => '<xml> 1 This is a really large string used to test certain large data types like xml data type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>');
    $r;
    $stmt1 = insertRow($db, $tbname, $inputs, "prepareExecuteBind", $r);
    var_dump($r);

    dropTable($db, $tbname);
    unset($stmt);
    unset($stmt1);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECT--
array(8) {
  ["IntCol"]=>
  string(1) "1"
  ["CharCol"]=>
  string(10) "STRINGCOL1"
  ["NCharCol"]=>
  string(10) "STRINGCOL1"
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.110"
  ["VarcharCol"]=>
  string(10) "STRINGCOL1"
  ["NVarCharCol"]=>
  string(10) "STRINGCOL1"
  ["FloatCol"]=>
  string(7) "111.111"
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
bool(true)
bool(true)
