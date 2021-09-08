--TEST--
starts a transaction, insert 2 rows and commit the transaction
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require('connect.inc');
    
    //make connection and create a temporaty table
    $conn = new PDO( "sqlsrv:Server=$server; Database = $databaseName ", "$uid", "$pwd");
    $tableName = "pdoBeginTransaction";
    dropTable($conn, $tableName);

    $conn->exec("CREATE TABLE $tableName(col1 CHARACTER(1), col2 CHARACTER(1)) ");

    $conn->beginTransaction();
    $ret = $conn->exec("insert into $tableName(col1, col2) values('a', 'b') ");
    $ret = $conn->exec("insert into $tableName(col1, col2) values('a', 'c') ");

    //revert the inserts
    $ret = $conn->exec("delete from $tableName where col1 = 'a'");
    $conn->commit();
    
    // $conn->rollback();
    echo $ret." rows affected";

    //drop the created temp table
    dropTable($conn, $tableName, false);

    unset($conn);
?>
--EXPECT--
2 rows affected