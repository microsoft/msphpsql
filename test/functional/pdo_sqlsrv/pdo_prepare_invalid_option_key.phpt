--TEST--
Test PDO::prepare by passing in a string key
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();

    $stmt = $conn->prepare("SELECT 1", array( "PDO::ATTR_CURSOR" => PDO::CURSOR_FWDONLY ));

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
  int(-43)
  [2]=>
  string(42) "An invalid statement option was specified."
}
