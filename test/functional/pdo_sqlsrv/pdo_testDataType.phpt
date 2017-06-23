--TEST--
Test the different type of data for retrieving
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once 'MsCommon.inc';


function testBigInt()
{
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT BigIntCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testBit()
{
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT BitCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);	
}

function testInt()
{
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT IntCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testSmallInt()
{
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT SmallIntCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testTinyInt()
{
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT TinyIntCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testDecimal()
{
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT DecimalCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testNumeric()
{
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT NumCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testMoney()
{	
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT MoneyCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testSmallMoney()
{
	$db = connect();
	// Retrieve data type
	$stmt = $db->query("SELECT SmallMoneyCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testFloat()
{
	$db = connect();
	$stmt = $db->query("SELECT FloatCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testReal()
{
	$db = connect();
	$stmt = $db->query("SELECT RealCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testChar()
{
	$db = connect();
	$stmt = $db->query("SELECT CharCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testVarchar()
{
	$db = connect();
	$stmt = $db->query("SELECT VarcharCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testText()
{
	$db = connect();
	$stmt = $db->query("SELECT TextCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testNText()
{
	$db = connect();
	$stmt = $db->query("SELECT NTextCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testNChar()
{
	$db = connect();
	$stmt = $db->query("SELECT NCharCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testNVarchar()
{
	$db = connect();
	$stmt = $db->query("SELECT NVarcharCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testImage()
{
	$db = connect();
	$stmt = $db->query("SELECT ImageCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testBinary()
{
	$db = connect();
	$stmt = $db->query("SELECT BinaryCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testVarbinary()
{
	$db = connect();
	$stmt = $db->query("SELECT VarbinaryCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testDateTime2()
{
	$db = connect();
	$stmt = $db->query("SELECT DateTime2Col FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testDatetimeoffset()
{
	$db = connect();
	$stmt = $db->query("SELECT DTOffsetCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testsmalldatetime()
{
	$db = connect();
	$stmt = $db->query("SELECT SmallDTCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testDateTime()
{
	$db = connect();
	$stmt = $db->query("SELECT DateTimeCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testDate()
{
	$db = connect();
	$stmt = $db->query("SELECT DateCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testNVarcharMax()
{
	$db = connect();
	$stmt = $db->query("SELECT NVarCharMaxCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testTime()
{
	$db = connect();
	$stmt = $db->query("SELECT TimeCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testUniqueidentifier()
{
	$db = connect();
	$stmt = $db->query("SELECT Guidcol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testVarbinaryMax()
{
	$db = connect();
	$stmt = $db->query("SELECT VarbinaryMaxCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testVarcharMax()
{
	$db = connect();
	$stmt = $db->query("SELECT VarcharMaxCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}

function testXml()
{
	$db = connect();
	$stmt = $db->query("SELECT XmlCol FROM PDO_AllTypes");
	$result = $stmt->fetch(PDO::FETCH_ASSOC);
	var_dump($result);
}


try
{
	//$db = connect();
	//$sql = "INSERT INTO PDO_AllTypes(BigIntCol,BitCol,IntCol,) VALUES(" . GetSampleData(4) . ",1,)";
	//$numRows = $db->exec($sql);
	
	echo "Test_1 : bigint data type :\n";
	testBigInt();
	echo "Test_2 : bit data type :\n";
	testBit();
	echo "Test_3 : int data type :\n";
	testInt();
	echo "Test_4 : smallint data type:\n";
	testSmallInt();
	echo "Test_5 : tinyint data type:\n";
	testTinyInt();
	echo "Test_6 : decimal data type:\n";
	testDecimal();	
	echo "Test_7 : numeric data type:\n";
	testNumeric();  
	echo "Test_8 : money data type:\n";
	testMoney();
	echo "Test_9 : smallmoney data type:\n";
	testSmallMoney();	
	echo "Test_10 : float data type:\n";
	testFloat();
	echo "Test_11 : real data type:\n";
	testReal();
	echo "Test_12 : char data type:\n";
	testChar();
	echo "Test_13 : varchar data type:\n";
	testVarchar();
	echo "Test_14 : text data type:\n";
	testText();
	echo "Test_15 : nchar data type:\n";
	testNChar();
	echo "Test_16 : nvarchar data type:\n";
	testNVarchar();	
	echo "Test_17 : image data type:\n";
	testImage();
	echo "Test_18 : binary data type:\n";
	testBinary();
	echo "Test_19 : varbinary data type:\n";
	testVarbinary();
	echo "Test_20 : smalldatetime data type:\n";
	testsmalldatetime();
	echo "Test_21 : datetime data type:\n";
	testDateTime();
	echo "Test_22 : datetime2 data type:\n";
	testsmalldatetime();
	echo "Test_23 : datetimeoffset data type:\n";
	testDatetimeoffset();
	echo "Test_24 : time data type:\n";
	testTime();
	echo "Test_25 : Uniqueidentifier data type:\n";
	testUniqueidentifier();
	echo "Test_26 : VarbinaryMax data type:\n";
	testVarbinaryMax();
	echo "Test_27 : VarcharMax data type:\n";
	testVarcharMax();
	echo "Test_28 : xml data type:\n";
	testXml();
	echo "Test_29 : ntext data type:\n";
	testNText();
	echo "Test_30 : nvarcharmax data type:\n";
	testNVarcharMax();
	
}
catch (PDOException $e)
{
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
  string(1) " "
}
Test_18 : binary data type:
array(1) {
  ["BinaryCol"]=>
  string(5) "     "
}
Test_19 : varbinary data type:
array(1) {
  ["VarbinaryCol"]=>
  string(1) " "
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
  string(1) " "
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