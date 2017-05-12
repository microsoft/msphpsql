--TEST--
Test PDOStatement::columnCount if the number of the columns in a result set.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    require_once 'MsCommon.inc';
    
    try
    {
        $db = connect();
        $sql = "SELECT * FROM PDO_AllTypes";
        $stmt = $db->prepare($sql);
        $stmt->execute();
        print_r("Existing table contains: " . $stmt->columnCount());
    }
    catch(PDOException $e)
    {
        var_dump($e);
    }
?>
--EXPECT--
Existing table contains: 31