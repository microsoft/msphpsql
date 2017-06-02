--TEST--
Test PDOStatement::execute method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require_once 'MsCommon.inc';

    try
    {
        $db = connect();

        $stmt = $db->prepare("SELECT * FROM PDO_Types_1");
        $stmt->execute();
        $rows = $stmt->fetch(PDO::FETCH_ASSOC);
        var_dump($rows);
        // Test update table value
        $stmt = $db->prepare("UPDATE PDO_Types_1 SET IntCol=1");
        $rows = $stmt->execute();
        var_dump($rows);
        // Test insert value to table
        $stmt1 = $db->prepare("INSERT INTO PDO_Types_1 (IntCol,CharCol,NCharCol,DateTimeCol,VarcharCol,NVarCharCol,FloatCol,XmlCol) VALUES (2,'STRINGCOL1','STRINGCOL1','2000-11-11 11:11:11.110','STRINGCOL1','STRINGCOL1',111.111,'<xml> 1 This is a really large string used to test certain large data types like xml data type. The fetch tests are the primary consumer of this string to validate that fetch on large types work fine. The length of this string as counted in terms of number of characters is 417.</xml>')");
        $rows = $stmt1->execute();
        var_dump($rows);
        $db = null;
    }
    catch(PDOException $e)
    {
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
