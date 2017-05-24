--TEST--
Test PDOStatement::errorInfo and PDOStatement::errorCode methods.
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
    $arr = $stmt->errorInfo();
    print_r("Error Info :\n");
    var_dump($arr);
    $arr = $stmt->errorCode();
    print_r("Error Code : " . $arr . "\n");
    
    
    
    $db = null;
}
catch (PDOException $e)
{
    var_dump($e);
}

?>
--EXPECT--
Error Info :
array(3) {
  [0]=>
  string(5) "00000"
  [1]=>
  NULL
  [2]=>
  NULL
}
Error Code : 00000