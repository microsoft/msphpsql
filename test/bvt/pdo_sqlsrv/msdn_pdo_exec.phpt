--TEST--
execute a delete and reports how many rows were deleted
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require('connect.inc');
    $c = new PDO("sqlsrv:server=$server; Database = $databaseName", $uid, $pwd);

    $tableName = "pdoExec";
    dropTable($c, $tableName);

    $c->exec("CREATE TABLE $tableName(col1 VARCHAR(100), col2 VARCHAR(100)) ");

    $ret = $c->exec("INSERT INTO $tableName VALUES('xxxyy', 'yyxx')");
    $ret = $c->exec("DELETE FROM $tableName WHERE col1 = 'xxxyy'");
    echo $ret," rows affected";

    dropTable($c, $tableName, false);

    //free the statement and connection
    unset($c);
?>
--EXPECT--
1 rows affected