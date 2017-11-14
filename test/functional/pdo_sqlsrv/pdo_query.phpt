--TEST--
Test the PDO::query() method.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

function queryDefault($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetch();
    var_dump($result);
}

function queryColumn($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname", PDO::FETCH_COLUMN, 2);
    $result = $stmt->fetch();
    var_dump($result);
}

function queryClass($conn, $tbname)
{
    global $mainTypesClass;
    $stmt = $conn->query("Select * from $tbname", PDO::FETCH_CLASS, $mainTypesClass);
    $result = $stmt->fetch();
    $result->dumpAll();
}

function queryInto($conn, $tbname)
{
    global $mainTypesClass;
    $obj = new $mainTypesClass;
    $stmt = $conn->query("Select * from $tbname", PDO::FETCH_INTO, $obj);
    $result = $stmt->fetch();
    $result->dumpAll();
}

function queryEmptyTable($conn)
{
    createTable($conn, 'emptyTable', array("c1" => "int", "c2" => "int"));
    $stmt = $conn->query("Select * from emptyTable");
    $result = $stmt->fetch();
    var_dump($result);
    dropTable($conn, 'emptyTable');
}

try {
    $db = connect();
    $tbname = "PDO_MainTypes";
    createAndInsertTableMainTypes($db, $tbname);
    echo "TEST_1 : query with default fetch style :\n";
    queryDefault($db, $tbname);

    echo "TEST_2 : query with FETCH_COLUMN :\n";
    queryColumn($db, $tbname);

    echo "TEST_3 : query with FETCH_CLASS :\n";
    queryClass($db, $tbname);

    echo "TEST_4 : query with FETCH_INTO :\n";
    queryInto($db, $tbname);

    echo "TEST_5 : query an empty table :\n";
    queryEmptyTable($db);

    dropTable($db, $tbname);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}


?>
--EXPECT--
TEST_1 : query with default fetch style :
array(16) {
  ["IntCol"]=>
  string(1) "1"
  [0]=>
  string(1) "1"
  ["CharCol"]=>
  string(10) "STRINGCOL1"
  [1]=>
  string(10) "STRINGCOL1"
  ["NCharCol"]=>
  string(10) "STRINGCOL1"
  [2]=>
  string(10) "STRINGCOL1"
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.110"
  [3]=>
  string(23) "2000-11-11 11:11:11.110"
  ["VarcharCol"]=>
  string(10) "STRINGCOL1"
  [4]=>
  string(10) "STRINGCOL1"
  ["NVarCharCol"]=>
  string(10) "STRINGCOL1"
  [5]=>
  string(10) "STRINGCOL1"
  ["FloatCol"]=>
  string(7) "111.111"
  [6]=>
  string(7) "111.111"
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
  [7]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
TEST_2 : query with FETCH_COLUMN :
string(10) "STRINGCOL1"
TEST_3 : query with FETCH_CLASS :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
TEST_4 : query with FETCH_INTO :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
TEST_5 : query an empty table :
bool(false)
