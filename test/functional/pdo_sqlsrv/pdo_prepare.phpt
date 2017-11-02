--TEST--
Test the PDO::prepare() method.
--ENV--
PHPT_EXEC=true
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

    // Test_1 : Test with no parameters
    echo "Test_1 : Test with no parameters :\n";
    $stmt = $db->prepare("select * from $tbname");
    if (!$stmt->execute()) {
        die("Test_1 failed.");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);

    // Test_2 : Test with indexed parameters
    echo "Test_2 : Test with indexed parameters :\n";
    $stmt = $db->prepare("select IntCol, CharCol from $tbname where IntCol = ? and CharCol = ?");
    if (!$stmt->execute(array(1, 'STRINGCOL1'))) {
        die("Test_2 failed.");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);

    // Test_3 : Test with named parameters
    echo "Test_3 : Test with named parameters :\n";
    $IntColVal = 2;
    $CharColVal = 'STRINGCOL2';
    $stmt = $db->prepare("select IntCol, CharCol from $tbname where IntCol = :IntColVal and CharCol = :CharColVal");
    if (!$stmt->execute(array(':IntColVal' => $IntColVal, ':CharColVal' => $CharColVal))) {
        die("Test_3 failed.");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);

    // Test_4: Test single prepare, multiple execution with indexed parameters
    echo "Test_4 : Test single prepare, multiple execution with indexed parameters :\n";
    $IntColVal = 1;
    $stmt = $db->prepare("select IntCol from $tbname where IntCol = ?");
    $stmt->bindParam(1, $IntColVal);
    if (!$stmt->execute()) {
        die("Test_4 failed.");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);

    // Execute the stmt again
    $IntColVal = 2;
    if (!$stmt->execute()) {
        die("Test_4 failed.");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);

    // Test_5: Test single prepare, multiple execution with named parameters
    echo "Test_5 : Test single prepare, multiple execution with named parameters :\n";
    $IntColVal = 1;
    $stmt = $db->prepare("select IntCol from $tbname where IntCol = :IntColVal");
    $stmt->bindParam(':IntColVal', $IntColVal);
    if (!$stmt->execute()) {
        die("Test_5 failed.");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);

    // Execute the stmt again
    $IntColVal = 2;
    if (!$stmt->execute()) {
        die("Test_5 failed.");
    }
    $result = $stmt->fetch(PDO::FETCH_ASSOC);
    var_dump($result);
} catch (PDOException $err) {
    var_dump($err);
    exit();
}
?>
--EXPECT--
Test_1 : Test with no parameters :
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
Test_2 : Test with indexed parameters :
array(2) {
  ["IntCol"]=>
  string(1) "1"
  ["CharCol"]=>
  string(10) "STRINGCOL1"
}
Test_3 : Test with named parameters :
array(2) {
  ["IntCol"]=>
  string(1) "2"
  ["CharCol"]=>
  string(10) "STRINGCOL2"
}
Test_4 : Test single prepare, multiple execution with indexed parameters :
array(1) {
  ["IntCol"]=>
  string(1) "1"
}
array(1) {
  ["IntCol"]=>
  string(1) "2"
}
Test_5 : Test single prepare, multiple execution with named parameters :
array(1) {
  ["IntCol"]=>
  string(1) "1"
}
array(1) {
  ["IntCol"]=>
  string(1) "2"
}
