--TEST--
Test PDO::__Construct connection option TransactionIsolation
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
function connectTransaction($value)
{
    $conn = connect("TransactionIsolation = $value");
    if (is_object($conn) && get_class($conn) == "PDO") {
        echo "Test Successful\n";
    }
    unset($conn);
}

// TEST BEGIN
try {
    connectTransaction("READ_UNCOMMITTED");
    connectTransaction("READ_COMMITTED");
    connectTransaction("REPEATABLE_READ");
    connectTransaction("SNAPSHOT");
    connectTransaction("SERIALIZABLE");
    connectTransaction("INVALID_KEY");
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Test Successful
Test Successful
Test Successful
Test Successful
Test Successful
array(3) {
  [0]=>
  string(5) "IMSSP"
  [1]=>
  int(-63)
  [2]=>
  string(88) "An invalid value was specified for the keyword 'TransactionIsolation' in the DSN string."
}
