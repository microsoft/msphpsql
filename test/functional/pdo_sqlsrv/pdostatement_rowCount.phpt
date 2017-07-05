--TEST--
Test PDOStatement::rowCount by adding, deleting or change
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once 'MsCommon.inc';

function createTmpTable()
{
    $db = connect();
    $sql = "CREATE TABLE tmp_table( val int)";
    $numRows = $db->exec($sql);
    if($numRows === false)
    {
        die("Create table failed");
    }
}

function insertTmpTable()
{
    $db = connect();
    $count = $db->prepare("INSERT INTO tmp_table (val) VALUES (123)");
    $count->execute();
    $no = $count->rowCount();
    print_r("Number of row after insertion: " . $no . "\n");
}

function updateRecord()
{
    $db = connect();
    $count = $db->prepare("UPDATE tmp_table set val=111");
    $count->execute();
    $no=$count->rowCount();
    print_r("Number of row after update: " . $no . "\n");
}

function deleteRecord()
{
    $db = connect();
    $del = $db->prepare("DELETE FROM tmp_table");
    $del->execute();
    $count = $del->rowCount();
    print_r("Number of rows been deleted: " . $count . "\n");
}

try{
    $db = connect();
    createTmpTable();
    insertTmpTable();
    updateRecord();
    insertTmpTable();
    deleteRecord();
    $db->exec("DROP TABLE tmp_table");
}
catch(PDOException $e)
{
    var_dump($e);
}

?>
--EXPECT--
Number of row after insertion: 1
Number of row after update: 1
Number of row after insertion: 1
Number of rows been deleted: 2
