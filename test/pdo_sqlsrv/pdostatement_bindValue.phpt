--TEST--
Test bindValue method.
--SKIPIF--
<?php require "skipif.inc"; ?>
--FILE--
<?php

require_once 'MsCommon.inc';

try{
    $db = connect();
    global $table2;
    $bigint = 1;
    $string = "STRINGCOL1";
    $stmt = $db->prepare("SELECT IntCol FROM " . $table2 . " WHERE BigIntCol = :bigint AND CharCol = :string");
    $stmt->bindValue(':bigint', $bigint, PDO::PARAM_INT);
    $stmt->bindValue(':string', $string, PDO::PARAM_STR);
    $stmt->execute();
    echo "Test Complete!\n";
}
catch (PDOException $e)
{
    var_dump($e);
}
?>
--EXPECT--
Test Complete!