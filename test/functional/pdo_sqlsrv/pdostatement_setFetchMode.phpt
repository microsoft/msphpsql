--TEST--
Test setFetchMode method.
--SKIPIF--
<?php require 'skipif.inc'; ?>
--FILE--
<?php

require_once 'MsCommon.inc';

try{
    $db = connect();
    $stmt = $db->query("SELECT * FROM " . $table1 );
    echo "Set Fetch Mode for PDO::FETCH_ASSOC \n";
    $stmt->setFetchMode(PDO::FETCH_ASSOC);
    $result = $stmt->fetch();
    var_dump($result);
    $stmt = $db->query("SELECT * FROM " . $table1 );
    echo "Set Fetch Mode for PDO::FETCH_NUM \n";
    $stmt->setFetchMode(PDO::FETCH_NUM);
    $result = $stmt->fetch();
    var_dump($result);
    $stmt = $db->query("SELECT * FROM " . $table1 );
    echo "Set Fetch Mode for PDO::FETCH_BOTH \n";
    $stmt->setFetchMode(PDO::FETCH_BOTH);
    $result = $stmt->fetch();
    var_dump($result);  
    $stmt = $db->query("SELECT * FROM " . $table1 );
    echo "Set Fetch Mode for PDO::FETCH_LAZY \n";
    $stmt->setFetchMode(PDO::FETCH_LAZY);
    $result = $stmt->fetch();
    var_dump($result);
    $stmt = $db->query("SELECT * FROM " . $table1 );
    echo "Set Fetch Mode for PDO::FETCH_OBJ \n";
    $stmt->setFetchMode(PDO::FETCH_OBJ);
    $result = $stmt->fetch();
    var_dump($result);
} 
catch ( PDOException $e)
{
    var_dump($e);
}

?>
--EXPECT--
Set Fetch Mode for PDO::FETCH_ASSOC 
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
Set Fetch Mode for PDO::FETCH_NUM 
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
Set Fetch Mode for PDO::FETCH_BOTH 
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
Set Fetch Mode for PDO::FETCH_LAZY 
object(PDORow)#3 (9) {
  ["queryString"]=>
  string(25) "SELECT * FROM PDO_Types_1"
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
Set Fetch Mode for PDO::FETCH_OBJ 
object(stdClass)#5 (8) {
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
