--TEST--
Test the PDOStatement::fetch() method with different fetch styles.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

function fetchWithStyle($conn, $tbname, $style)
{
    $stmt = $conn->query("SELECT * FROM $tbname");
    switch ($style) {
        case PDO::FETCH_BOTH:
        case PDO::FETCH_ASSOC:
        case PDO::FETCH_LAZY:
        case PDO::FETCH_OBJ:
        case PDO::FETCH_NUM:
        {
            $result = $stmt->fetch($style);
            var_dump($result);
            unset($stmt);
            break;
        }
        case PDO::FETCH_BOUND:
        {
            $stmt->bindColumn('IntCol', $IntCol);
            $stmt->bindColumn('CharCol', $CharCol);
            $stmt->bindColumn('NCharCol', $NCharCol);
            $stmt->bindColumn('DateTimeCol', $DateTimeCol);
            $stmt->bindColumn('VarcharCol', $VarcharCol);
            $stmt->bindColumn('NVarCharCol', $NVarCharCol);
            $stmt->bindColumn('FloatCol', $FloatCol);
            $stmt->bindColumn('XmlCol', $XmlCol);
            $result = $stmt->fetch($style);
            if (!$result) {
                die("Error in FETCH_BOUND\n");
            }
            var_dump($IntCol);
            var_dump($CharCol);
            var_dump($NCharCol);
            var_dump($DateTimeCol);
            var_dump($VarcharCol);
            var_dump($NVarCharCol);
            var_dump($FloatCol);
            var_dump($XmlCol);
            unset($stmt);
            break;
        }
        case PDO::FETCH_CLASS:
        {
            global $mainTypesClass;
            $stmt->setFetchMode($style, $mainTypesClass);
            $result = $stmt->fetch($style);
            $result->dumpAll();
            unset($stmt);
            break;
        }
        case PDO::FETCH_INTO:
        {
            global $mainTypesClass;
            $obj = new $mainTypesClass;
            $stmt->setFetchMode($style, $obj);
            $result = $stmt->fetch($style);
            $obj->dumpAll();
            unset($stmt);
            break;
        }
        case "PDO::FETCH_INVALID":
        {
            try {
                $result = $stmt->fetch(PDO::FETCH_UNKNOWN);
            } catch (PDOException $err) {
                print_r($err);
            }
            break;
        }
        
    }
}

try {
    $db = connect();
    $tbname = "PDO_MainTypes";
    createAndInsertTableMainTypes($db, $tbname);
    echo "Test_1 : FETCH_BOTH :\n";
    fetchWithStyle($db, $tbname, PDO::FETCH_BOTH);
    echo "Test_2 : FETCH_ASSOC :\n";
    fetchWithStyle($db, $tbname, PDO::FETCH_ASSOC);
    echo "Test_3 : FETCH_LAZY :\n";
    fetchWithStyle($db, $tbname, PDO::FETCH_LAZY);
    echo "Test_4 : FETCH_OBJ :\n";
    fetchWithStyle($db, $tbname, PDO::FETCH_OBJ);
    echo "Test_5 : FETCH_NUM :\n";
    fetchWithStyle($db, $tbname, PDO::FETCH_NUM);
    echo "Test_6 : FETCH_BOUND :\n";
    fetchWithStyle($db, $tbname, PDO::FETCH_BOUND);
    echo "Test_7 : FETCH_CLASS :\n";
    fetchWithStyle($db, $tbname, PDO::FETCH_CLASS);
    echo "Test_8 : FETCH_INTO :\n";
    fetchWithStyle($db, $tbname, PDO::FETCH_INTO);
    echo "Test_9 : FETCH_INVALID :\n";
    fetchWithStyle($db, $tbname, "PDO::FETCH_INVALID");

    dropTable($db, $tbname);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}


?>

--EXPECTF--
Test_1 : FETCH_BOTH :
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
Test_2 : FETCH_ASSOC :
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
Test_3 : FETCH_LAZY :
object(PDORow)#%x (%x) {
  ["queryString"]=>
  string(27) "SELECT * FROM PDO_MainTypes"
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
Test_4 : FETCH_OBJ :
object(stdClass)#%x (%x) {
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
Test_5 : FETCH_NUM :
array(8) {
  [0]=>
  string(1) "1"
  [1]=>
  string(10) "STRINGCOL1"
  [2]=>
  string(10) "STRINGCOL1"
  [3]=>
  string(23) "2000-11-11 11:11:11.110"
  [4]=>
  string(10) "STRINGCOL1"
  [5]=>
  string(10) "STRINGCOL1"
  [6]=>
  string(7) "111.111"
  [7]=>
  string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
}
Test_6 : FETCH_BOUND :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
Test_7 : FETCH_CLASS :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
Test_8 : FETCH_INTO :
string(1) "1"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(23) "2000-11-11 11:11:11.110"
string(10) "STRINGCOL1"
string(10) "STRINGCOL1"
string(7) "111.111"
string(431) "<xml> 1 This is a really large string used to test certain large data types like xml data type. The length of this string is greater than 256 to correctly test a large data type. This is currently used by atleast varchar type and by xml type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>"
Test_9 : FETCH_INVALID :

Fatal error: Uncaught Error: Undefined class constant 'FETCH_UNKNOWN' in %s:%x
Stack trace:
#0 %s: fetchWithStyle(Object(PDO), 'PDO_MainTypes', 'PDO::FETCH_INVA...')
#1 {main}
  thrown in %s on line %x
