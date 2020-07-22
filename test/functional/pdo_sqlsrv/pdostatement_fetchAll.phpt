--TEST--
Test the PDOStatement::fetchAll() method with various arguments (Note: FETCH_LAZY/FETCH_INTO/FETCH_BOUND are not tested).
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

function fetchAllRows($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetchAll();
    var_dump($result);
    unset($stmt);
}

function fetchAllColumn($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetchAll(PDO::FETCH_COLUMN, 5);
    var_dump($result);
    unset($stmt);
}

function fetchAllTypes($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetchAll();
    var_dump($result);
    unset($stmt);
}

function fetchAllBoth($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetchAll(PDO::FETCH_BOTH);
    var_dump($result[0]);
    unset($stmt);
}

function fetchAllAssoc($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($result[0]);
    unset($stmt);
}

function fetchAllObj($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetchAll(PDO::FETCH_OBJ);
    var_dump($result[1]);
    unset($stmt);
}

function fetchAllNum($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetchAll(PDO::FETCH_NUM);
    var_dump($result[1]);
    unset($stmt);
}

function fetchAllClass($conn, $tbname)
{
    global $mainTypesClass;
    $stmt = $conn->query("Select * from $tbname");
    $result = $stmt->fetchAll(PDO::FETCH_CLASS, $mainTypesClass);
    $result[1]->dumpAll();
    unset($stmt);
}

function fetchAllInvalid($conn, $tbname)
{
    $stmt = $conn->query("Select * from $tbname");
    try {
        $result = $stmt->fetchAll(PDO::FETCH_UNKNOWN);
    } catch (PDOException $ex) {
        print_r($ex);
    } catch (Error $err) {
        if (PHP_MAJOR_VERSION == 8) {
            $message = "Undefined constant PDO::FETCH_UNKNOWN";
        } else {
            $message = "Undefined class constant 'FETCH_UNKNOWN'";
        }
        if ($err->getMessage() !== $message) {
            echo $err->getMessage() . PHP_EOL;
        }
    }
}

// When testing with PHP 8.0 it throws a TypeError instead of a warning. Thus implement a custom 
// warning handler such that with PHP 7.x the warning would be handled to throw a TypeError.
// Sometimes the error messages from PHP 8.0 may be different and have to be handled differently.
function warningHandler($errno, $errstr) 
{ 
    throw new Error($errstr);
}

try {
    $db = connect();
    $tbname1 = "PDO_MainTypes";
    $tbname2 = "PDO_AllTypes";
    createAndInsertTableMainTypes($db, $tbname1);
    createAndInsertTableAllTypes($db, $tbname2);
    echo "Test_1 : fetch from a table with multiple rows :\n";
    fetchAllRows($db, $tbname1);
    echo "Test_2 : fetch all values of a single column :\n";
    fetchAllColumn($db, $tbname1);
    echo "Test_3 : fetch all SQL types except TimeStamp and Variant :\n";
    fetchAllTypes($db, $tbname2);
    echo "Test_4 : FETCH_BOTH :\n";
    fetchAllBoth($db, $tbname1);
    echo "Test_5 : FETCH_ASSOC :\n";
    fetchAllAssoc($db, $tbname1);
    echo "Test_6 : FETCH_OBJ :\n";
    fetchAllObj($db, $tbname1);
    echo "Test_7 : FETCH_NUM :\n";
    fetchAllNum($db, $tbname1);
    echo "Test_8 : FETCH_CLASS :\n";
    fetchAllClass($db, $tbname1);
    echo "Test_9 : FETCH_INVALID :\n";

    set_error_handler("warningHandler", E_WARNING);
    fetchAllInvalid($db, $tbname1);
    restore_error_handler();

    dropTable($db, $tbname1);
    dropTable($db, $tbname2);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>
--EXPECTF--
Test_1 : fetch from a table with multiple rows :
array(2) {
  [0]=>
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
    string(%d) "111.111%S"
    [6]=>
    string(%d) "111.111%S"
    ["XmlCol"]=>
    string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
    [7]=>
    string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
  }
  [1]=>
  array(16) {
    ["IntCol"]=>
    string(1) "2"
    [0]=>
    string(1) "2"
    ["CharCol"]=>
    string(10) "STRINGCOL2"
    [1]=>
    string(10) "STRINGCOL2"
    ["NCharCol"]=>
    string(10) "STRINGCOL2"
    [2]=>
    string(10) "STRINGCOL2"
    ["DateTimeCol"]=>
    string(23) "2000-11-11 11:11:11.223"
    [3]=>
    string(23) "2000-11-11 11:11:11.223"
    ["VarcharCol"]=>
    string(10) "STRINGCOL2"
    [4]=>
    string(10) "STRINGCOL2"
    ["NVarCharCol"]=>
    string(10) "STRINGCOL2"
    [5]=>
    string(10) "STRINGCOL2"
    ["FloatCol"]=>
    string(%d) "222.222%S"
    [6]=>
    string(%d) "222.222%S"
    ["XmlCol"]=>
    string(431) "<xml> 2 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
    [7]=>
    string(431) "<xml> 2 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
  }
}
Test_2 : fetch all values of a single column :
array(2) {
  [0]=>
  string(10) "STRINGCOL1"
  [1]=>
  string(10) "STRINGCOL2"
}
Test_3 : fetch all SQL types except TimeStamp and Variant :
array(1) {
  [0]=>
  array(62) {
    ["BigIntCol"]=>
    string(1) "1"
    [0]=>
    string(1) "1"
    ["BinaryCol"]=>
    string(5) "abcde"
    [1]=>
    string(5) "abcde"
    ["BitCol"]=>
    string(1) "0"
    [2]=>
    string(1) "0"
    ["CharCol"]=>
    string(10) "STRINGCOL1"
    [3]=>
    string(10) "STRINGCOL1"
    ["DateCol"]=>
    string(10) "2000-11-11"
    [4]=>
    string(10) "2000-11-11"
    ["DateTimeCol"]=>
    string(23) "2000-11-11 11:11:11.110"
    [5]=>
    string(23) "2000-11-11 11:11:11.110"
    ["DateTime2Col"]=>
    string(27) "2000-11-11 11:11:11.1110000"
    [6]=>
    string(27) "2000-11-11 11:11:11.1110000"
    ["DTOffsetCol"]=>
    string(34) "2000-11-11 11:11:11.1110000 +00:00"
    [7]=>
    string(34) "2000-11-11 11:11:11.1110000 +00:00"
    ["DecimalCol"]=>
    string(3) "111"
    [8]=>
    string(3) "111"
    ["FloatCol"]=>
    string(%d) "111.111%S"
    [9]=>
    string(%d) "111.111%S"
    ["ImageCol"]=>
    string(5) "abcde"
    [10]=>
    string(5) "abcde"
    ["IntCol"]=>
    string(1) "1"
    [11]=>
    string(1) "1"
    ["MoneyCol"]=>
    string(8) "111.1110"
    [12]=>
    string(8) "111.1110"
    ["NCharCol"]=>
    string(10) "STRINGCOL1"
    [13]=>
    string(10) "STRINGCOL1"
    ["NTextCol"]=>
    string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
    [14]=>
    string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
    ["NumCol"]=>
    string(1) "1"
    [15]=>
    string(1) "1"
    ["NVarCharCol"]=>
    string(10) "STRINGCOL1"
    [16]=>
    string(10) "STRINGCOL1"
    ["NVarCharMaxCol"]=>
    string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
    [17]=>
    string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
    ["RealCol"]=>
    string(%d) "111.111%S"
    [18]=>
    string(%d) "111.111%S"
    ["SmallDTCol"]=>
    string(19) "2000-11-11 11:11:00"
    [19]=>
    string(19) "2000-11-11 11:11:00"
    ["SmallIntCol"]=>
    string(1) "1"
    [20]=>
    string(1) "1"
    ["SmallMoneyCol"]=>
    string(8) "111.1110"
    [21]=>
    string(8) "111.1110"
    ["TextCol"]=>
    string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
    [22]=>
    string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
    ["TimeCol"]=>
    string(16) "11:11:11.1110000"
    [23]=>
    string(16) "11:11:11.1110000"
    ["TinyIntCol"]=>
    string(1) "1"
    [24]=>
    string(1) "1"
    ["Guidcol"]=>
    string(36) "AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA"
    [25]=>
    string(36) "AAAAAAAA-AAAA-AAAA-AAAA-AAAAAAAAAAAA"
    ["VarbinaryCol"]=>
    string(5) "abcde"
    [26]=>
    string(5) "abcde"
    ["VarbinaryMaxCol"]=>
    string(5) "abcde"
    [27]=>
    string(5) "abcde"
    ["VarcharCol"]=>
    string(10) "STRINGCOL1"
    [28]=>
    string(10) "STRINGCOL1"
    ["VarcharMaxCol"]=>
    string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
    [29]=>
    string(420) " 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417."
    ["XmlCol"]=>
    string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
    [30]=>
    string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
  }
}
Test_4 : FETCH_BOTH :
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
  string(%d) "111.111%S"
  [6]=>
  string(%d) "111.111%S"
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
  [7]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_5 : FETCH_ASSOC :
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
  string(%d) "111.111%S"
  ["XmlCol"]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_6 : FETCH_OBJ :
object(stdClass)#%x (%x) {
  ["IntCol"]=>
  string(1) "2"
  ["CharCol"]=>
  string(10) "STRINGCOL2"
  ["NCharCol"]=>
  string(10) "STRINGCOL2"
  ["DateTimeCol"]=>
  string(23) "2000-11-11 11:11:11.223"
  ["VarcharCol"]=>
  string(10) "STRINGCOL2"
  ["NVarCharCol"]=>
  string(10) "STRINGCOL2"
  ["FloatCol"]=>
  string(%d) "222.222%S"
  ["XmlCol"]=>
  string(431) "<xml> 2 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_7 : FETCH_NUM :
array(8) {
  [0]=>
  string(1) "2"
  [1]=>
  string(10) "STRINGCOL2"
  [2]=>
  string(10) "STRINGCOL2"
  [3]=>
  string(23) "2000-11-11 11:11:11.223"
  [4]=>
  string(10) "STRINGCOL2"
  [5]=>
  string(10) "STRINGCOL2"
  [6]=>
  string(%d) "222.222%S"
  [7]=>
  string(431) "<xml> 2 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_8 : FETCH_CLASS :
string(1) "2"
string(10) "STRINGCOL2"
string(10) "STRINGCOL2"
string(23) "2000-11-11 11:11:11.223"
string(10) "STRINGCOL2"
string(10) "STRINGCOL2"
string(%d) "222.222%S"
string(431) "<xml> 2 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
Test_9 : FETCH_INVALID :