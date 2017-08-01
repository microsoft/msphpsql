--TEST--
Test debugDumpParams method.
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
    
    $stmt->debugDumpParams();
}
catch (PDOException $e)
{
    var_dump($e);
}
?>
--EXPECT--
SQL: [79] SELECT IntCol FROM PDO_AllTypes WHERE BigIntCol = :bigint AND CharCol = :string
Params:  2
Key: Name: [7] :bigint
paramno=0
name=[7] ":bigint"
is_param=1
param_type=1
Key: Name: [7] :string
paramno=1
name=[7] ":string"
is_param=1
param_type=2