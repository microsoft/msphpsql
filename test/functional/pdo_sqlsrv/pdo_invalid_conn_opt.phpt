--TEST--
Test PDO::__Construct with invalid connection option
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("InvalidKey = true;");
    echo "Test Successful";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
array(3) {
  [0]=>
  string(5) "IMSSP"
  [1]=>
  int(-42)
  [2]=>
  string(64) "An invalid keyword 'InvalidKey' was specified in the DSN string."
}
Test Successful