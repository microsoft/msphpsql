--TEST--
Test the different type of data for retrieving
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

function testBigInt($db, $tbname)
{
    $stmt = $db->query("SELECT BigIntCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testBit($db, $tbname)
{
    $stmt = $db->query("SELECT BitCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testInt($db, $tbname)
{
    $stmt = $db->query("SELECT IntCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testSmallInt($db, $tbname)
{
    $stmt = $db->query("SELECT SmallIntCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testTinyInt($db, $tbname)
{
    $stmt = $db->query("SELECT TinyIntCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testDecimal($db, $tbname)
{
    $stmt = $db->query("SELECT DecimalCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testNumeric($db, $tbname)
{
    $stmt = $db->query("SELECT NumCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testMoney($db, $tbname)
{
    $stmt = $db->query("SELECT MoneyCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testSmallMoney($db, $tbname)
{
    $stmt = $db->query("SELECT SmallMoneyCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testFloat($db, $tbname)
{
    $stmt = $db->query("SELECT FloatCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testReal($db, $tbname)
{
    $stmt = $db->query("SELECT RealCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testChar($db, $tbname)
{
    $stmt = $db->query("SELECT CharCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testVarchar($db, $tbname)
{
    $stmt = $db->query("SELECT VarcharCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testText($db, $tbname)
{
    $stmt = $db->query("SELECT TextCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testNText($db, $tbname)
{
    $stmt = $db->query("SELECT NTextCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testNChar($db, $tbname)
{
    $stmt = $db->query("SELECT NCharCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testNVarchar($db, $tbname)
{
    $stmt = $db->query("SELECT NVarcharCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testImage($db, $tbname)
{
    $stmt = $db->query("SELECT ImageCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testBinary($db, $tbname)
{
    $stmt = $db->query("SELECT BinaryCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testVarbinary($db, $tbname)
{
    $stmt = $db->query("SELECT VarbinaryCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testDateTime2($db, $tbname)
{
    $stmt = $db->query("SELECT DateTime2Col FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testDatetimeoffset($db, $tbname)
{
    $stmt = $db->query("SELECT DTOffsetCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testsmalldatetime($db, $tbname)
{
    $stmt = $db->query("SELECT SmallDTCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testDateTime($db, $tbname)
{
    $stmt = $db->query("SELECT DateTimeCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testDate($db, $tbname)
{
    $stmt = $db->query("SELECT DateCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testNVarcharMax($db, $tbname)
{
    $stmt = $db->query("SELECT NVarCharMaxCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testTime($db, $tbname)
{
    $stmt = $db->query("SELECT TimeCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testUniqueidentifier($db, $tbname)
{
    $stmt = $db->query("SELECT Guidcol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testVarbinaryMax($db, $tbname)
{
    $stmt = $db->query("SELECT VarbinaryMaxCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testVarcharMax($db, $tbname)
{
    $stmt = $db->query("SELECT VarcharMaxCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

function testXml($db, $tbname)
{
    $stmt = $db->query("SELECT XmlCol FROM $tbname");
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
}

try {
    $db = connect();
    $tbname = "PDO_AllTypes";
    createAndInsertTableAllTypes($db, $tbname);

    echo "Test_1 : bigint data type :\n";
    testBigInt($db, $tbname);
    echo "Test_2 : bit data type :\n";
    testBit($db, $tbname);
    echo "Test_3 : int data type :\n";
    testInt($db, $tbname);
    echo "Test_4 : smallint data type:\n";
    testSmallInt($db, $tbname);
    echo "Test_5 : tinyint data type:\n";
    testTinyInt($db, $tbname);
    echo "Test_6 : decimal data type:\n";
    testDecimal($db, $tbname);
    echo "Test_7 : numeric data type:\n";
    testNumeric($db, $tbname);
    echo "Test_8 : money data type:\n";
    testMoney($db, $tbname);
    echo "Test_9 : smallmoney data type:\n";
    testSmallMoney($db, $tbname);
    echo "Test_10 : float data type:\n";
    testFloat($db, $tbname);
    echo "Test_11 : real data type:\n";
    testReal($db, $tbname);
    echo "Test_12 : char data type:\n";
    testChar($db, $tbname);
    echo "Test_13 : varchar data type:\n";
    testVarchar($db, $tbname);
    echo "Test_14 : text data type:\n";
    testText($db, $tbname);
    echo "Test_15 : nchar data type:\n";
    testNChar($db, $tbname);
    echo "Test_16 : nvarchar data type:\n";
    testNVarchar($db, $tbname);
    echo "Test_17 : image data type:\n";
    testImage($db, $tbname);
    echo "Test_18 : binary data type:\n";
    testBinary($db, $tbname);
    echo "Test_19 : varbinary data type:\n";
    testVarbinary($db, $tbname);
    echo "Test_20 : smalldatetime data type:\n";
    testsmalldatetime($db, $tbname);
    echo "Test_21 : datetime data type:\n";
    testDateTime($db, $tbname);
    echo "Test_22 : datetime2 data type:\n";
    testsmalldatetime($db, $tbname);
    echo "Test_23 : datetimeoffset data type:\n";
    testDatetimeoffset($db, $tbname);
    echo "Test_24 : time data type:\n";
    testTime($db, $tbname);
    echo "Test_25 : Uniqueidentifier data type:\n";
    testUniqueidentifier($db, $tbname);
    echo "Test_26 : VarbinaryMax data type:\n";
    testVarbinaryMax($db, $tbname);
    echo "Test_27 : VarcharMax data type:\n";
    testVarcharMax($db, $tbname);
    echo "Test_28 : xml data type:\n";
    testXml($db, $tbname);
    echo "Test_29 : ntext data type:\n";
    testNText($db, $tbname);
    echo "Test_30 : nvarcharmax data type:\n";
    testNVarcharMax($db, $tbname);
    echo "Test_31 : date data type:\n";
    testDate($db, $tbname);

    dropTable($db, $tbname);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
}
?>

--EXPECT--
Test_1 : bigint data type :
array(1) {
  ["BigIntCol"]=>
  string(1) "1"
}
Test_2 : bit data type :
array(1) {
  ["BitCol"]=>
  string(1) "0"
}
Test_3 : int data type :
array(1) {
  ["IntCol"]=>
  string(1) "1"
}
Test_4 : smallint data type:
array(1) {
  ["SmallIntCol"]=>
  string(1) "1"
}
Test_5 : tinyint data type:
array(1) {
  ["TinyIntCol"]=>
  string(1) "1"
}
Test_6 : decimal data type:
array(1) {
  ["DecimalCol"]=>
  string(3) "111"
}
Test_7 : numeric data type:
array(1) {
  ["NumCol"]=>
  string(1) "1"
}
Test_8 : money data type:
array(1) {
  ["MoneyCol"]=>
  string(8) "111.1110"
}
Test_9 : smallmoney data type:
array(1) {
  ["SmallMoneyCol"]=>
  string(8) "111.1110"
}
Test_10 : float data type:
array(1) {
  ["FloatCol"]=>
  string(7) "111.111"
}
Test_11 : real data type:
array(1) {
  ["RealCol"]=>
  string(7) "111.111"
}
Test_12 : char data type:
array(1) {
  ["CharCol"]=>
  string(10) "STRINGCOL1"
}
Test_13 : varchar data type:
array(1) {
  ["VarcharCol"]=>
  string(10) "STRINGCOL1"
}
Test_14 : text data type:
array(1) {
  ["TextCol"]=>
  string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
}
Test_15 : nchar data type:
array(1) {
  ["NCharCol"]=>
  string(10) "STRINGCOL1"
}
Test_16 : nvarchar data type:
array(1) {
  ["NVarcharCol"]=>
  string(10) "STRINGCOL1"
}
Test_17 : image data type:
array(1) {
  ["ImageCol"]=>
  string(0) ""
}
Test_18 : binary data type:
array(1) {
  ["BinaryCol"]=>
  string(5) "00   "
}
Test_19 : varbinary data type:
array(1) {
  ["VarbinaryCol"]=>
  string(0) ""
}
Test_20 : smalldatetime data type:
array(1) {
  ["SmallDTCol"]=>
  string(19) "2000-11-11 11:11:00"
}
Test_21 : datetime data type:
array(1) {
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.110"
}
Test_22 : datetime2 data type:
array(1) {
  ["SmallDTCol"]=>
  string(19) "2000-11-11 11:11:00"
}
Test_23 : datetimeoffset data type:
array(1) {
  ["DTOffsetCol"]=>
  string(34) "2000-11-11 11:11:11.1110000 +00:00"
}
Test_24 : time data type:
array(1) {
  ["TimeCol"]=>
  string(16) "11:11:11.1110000"
}
Test_25 : Uniqueidentifier data type:
array(1) {
  ["Guidcol"]=>
  string(36) "AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA"
}
Test_26 : VarbinaryMax data type:
array(1) {
  ["VarbinaryMaxCol"]=>
  string(0) ""
}
Test_27 : VarcharMax data type:
array(1) {
  ["VarcharMaxCol"]=>
  string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
}
Test_28 : xml data type:
array(1) {
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_29 : ntext data type:
array(1) {
  ["NTextCol"]=>
  string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
}
Test_30 : nvarcharmax data type:
array(1) {
  ["NVarCharMaxCol"]=>
  string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
}
Test_31 : date data type:
array(1) {
  ["DateCol"]=>
  string(10) "2000-11-11"
}
