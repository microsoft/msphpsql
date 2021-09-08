--TEST--
GitHub issue 1258 - is_callable() throws an exception if PDOStatement method does not exist
--DESCRIPTION--
The test shows is_callable() will return false if PDOStatement method does not exist instead of throwing an exception. The user can still check errorInfo() for the error message. See documentation https://www.php.net/manual/en/function.is-callable.php
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $stmt = $conn->prepare("SELECT @@Version");
    $functionExists = is_callable([$stmt, 'bindParam'], false, $callable);
    var_dump($functionExists);
    var_dump($callable);

    $functionExists = is_callable([$stmt, 'boo']);
    var_dump($functionExists);
    
    echo PHP_EOL . "Error INFO:" . PHP_EOL;
    var_dump($conn->errorInfo());

    echo "Done\n";
} catch (PdoException $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
bool(true)
string(23) "PDOStatement::bindParam"
bool(false)

Error INFO:
array(3) {
  [0]=>
  string(5) "IMSSP"
  [1]=>
  int(-58)
  [2]=>
  string(48) "This function is not implemented by this driver."
}
Done

