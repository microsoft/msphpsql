--TEST--
Test PDOStatement::closeCursor method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once 'MsCommon.inc';

try{
    $db = connect();
    global $table2;
    $stmt = $db->prepare("SELECT * FROM " . $table2);
    $stmt->execute();
    $result = $stmt->fetch();
    $stmt->closeCursor();
    echo "Test complete!";
}
catch ( PDOException $e)
{
    var_dump($e);
}



?>

--EXPECT--
Test complete!