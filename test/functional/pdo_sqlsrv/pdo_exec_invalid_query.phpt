--TEST--
direct execution of an invalid query
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
    require_once("MsCommon_mid-refactor.inc");

    // set ERRMODE to silent to return in errorCode in the test
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    $tbname = "table1";
    dropTable($conn, $tbname);

    // execute a query with typo (spelling error in CREATE)
    $conn->exec("CRETE TABLE $tbname (id INT NOT NULL PRIMARY KEY, val VARCHAR(10)) ");
    print_r($conn->errorCode());
    echo "\n";

    // execute a properly formatted query
    $conn->exec("CREATE TABLE $tbname (id INT NOT NULL PRIMARY KEY, val VARCHAR(10)) ");
    print_r($conn->errorCode());
    echo "\n";

    // drop table1 and free connections
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
42000
00000
