--TEST--
Test the different type of data for retrieving
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

function testCol($db, $tbname, $colName)
{
    $stmt = $db->query("SELECT $colName FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}
try {
    $db = connect();
    $tbname = "PDO_AllTypes";
    createAndInsertTableAllTypes($db, $tbname);
    $colNames = array("BigIntCol", "BitCol", "IntCol", "SmallIntCol", "TinyIntCol", "DecimalCol", "NumCol", "MoneyCol", "SmallMoneyCol", "FloatCol", "RealCol", "CharCol", "VarcharCol", "TextCol", "NCharCol", "NVarcharCol", "ImageCol", "BinaryCol", "VarbinaryCol", "SmallDTCol", "DateTimeCol", "DateTime2Col", "DTOffsetCol", "TimeCol", "Guidcol", "VarbinaryMaxCol", "VarcharMaxCol", "XmlCol", "NTextCol", "NVarCharMaxCol", "DateCol");

    $i = 1;
    foreach($colNames as $colName) {
        echo "Test_$i : $colName :\n";
        testCol($db, $tbname, $colName);
        $i++;
    }
 
    dropTable($db, $tbname);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
}
?>

--EXPECTF--
Test_1 : BigIntCol :
array(1) {
  ["BigIntCol"]=>
  string(1) "1"
}
Test_2 : BitCol :
array(1) {
  ["BitCol"]=>
  string(1) "0"
}
Test_3 : IntCol :
array(1) {
  ["IntCol"]=>
  string(1) "1"
}
Test_4 : SmallIntCol :
array(1) {
  ["SmallIntCol"]=>
  string(1) "1"
}
Test_5 : TinyIntCol :
array(1) {
  ["TinyIntCol"]=>
  string(1) "1"
}
Test_6 : DecimalCol :
array(1) {
  ["DecimalCol"]=>
  string(3) "111"
}
Test_7 : NumCol :
array(1) {
  ["NumCol"]=>
  string(1) "1"
}
Test_8 : MoneyCol :
array(1) {
  ["MoneyCol"]=>
  string(8) "111.1110"
}
Test_9 : SmallMoneyCol :
array(1) {
  ["SmallMoneyCol"]=>
  string(8) "111.1110"
}
Test_10 : FloatCol :
array(1) {
  ["FloatCol"]=>
  string(%d) "111.111%S"
}
Test_11 : RealCol :
array(1) {
  ["RealCol"]=>
  string(7) "111.111"
}
Test_12 : CharCol :
array(1) {
  ["CharCol"]=>
  string(10) "STRINGCOL1"
}
Test_13 : VarcharCol :
array(1) {
  ["VarcharCol"]=>
  string(10) "STRINGCOL1"
}
Test_14 : TextCol :
array(1) {
  ["TextCol"]=>
  string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
}
Test_15 : NCharCol :
array(1) {
  ["NCharCol"]=>
  string(10) "STRINGCOL1"
}
Test_16 : NVarcharCol :
array(1) {
  ["NVarcharCol"]=>
  string(10) "STRINGCOL1"
}
Test_17 : ImageCol :
array(1) {
  ["ImageCol"]=>
  string(5) "abcde"
}
Test_18 : BinaryCol :
array(1) {
  ["BinaryCol"]=>
  string(5) "abcde"
}
Test_19 : VarbinaryCol :
array(1) {
  ["VarbinaryCol"]=>
  string(5) "abcde"
}
Test_20 : SmallDTCol :
array(1) {
  ["SmallDTCol"]=>
  string(19) "2000-11-11 11:11:00"
}
Test_21 : DateTimeCol :
array(1) {
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.110"
}
Test_22 : DateTime2Col :
array(1) {
  ["DateTime2Col"]=>
  string(27) "2000-11-11 11:11:11.1110000"
}
Test_23 : DTOffsetCol :
array(1) {
  ["DTOffsetCol"]=>
  string(34) "2000-11-11 11:11:11.1110000 +00:00"
}
Test_24 : TimeCol :
array(1) {
  ["TimeCol"]=>
  string(16) "11:11:11.1110000"
}
Test_25 : Guidcol :
array(1) {
  ["Guidcol"]=>
  string(36) "AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA"
}
Test_26 : VarbinaryMaxCol :
array(1) {
  ["VarbinaryMaxCol"]=>
  string(5) "abcde"
}
Test_27 : VarcharMaxCol :
array(1) {
  ["VarcharMaxCol"]=>
  string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
}
Test_28 : XmlCol :
array(1) {
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_29 : NTextCol :
array(1) {
  ["NTextCol"]=>
  string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
}
Test_30 : NVarCharMaxCol :
array(1) {
  ["NVarCharMaxCol"]=>
  string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
}
Test_31 : DateCol :
array(1) {
  ["DateCol"]=>
  string(10) "2000-11-11"
}
