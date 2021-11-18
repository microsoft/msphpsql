--TEST--
Confirm that PDO::ATTR_ERRMODE value should be restored whether PDO::lastInsertId() call succeeded or not.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();

    // create temporary tables
    createTable($conn, "table1", array(new columnMeta("int", "id", "IDENTITY(100,2)"), "val" => "int"));
    createTable($conn, "table2", array(new columnMeta("int", "id", "IDENTITY(200,2)"), "val" => "int"));
    createTable($conn, "table3", array("id" => "int", "val" => "int"));

    insertRow($conn, "table1", array("val" => 1), "exec");
    insertRow($conn, "table2", array("val" => 2), "exec");
    $conn->lastInsertId();
    var_dump($conn->getAttribute(PDO::ATTR_ERRMODE));

    insertRow($conn, "table2", array("val" => 3), "exec");
    insertRow($conn, "table1", array("val" => 4), "exec");
    $conn->lastInsertId();
    var_dump($conn->getAttribute(PDO::ATTR_ERRMODE));

    // Should restore original value even if PDO::lastInsertId() failed.
    insertRow($conn, "table3", array("id" => 1, "val" => 1), "exec");
    $conn->lastInsertId();
    var_dump($conn->getAttribute(PDO::ATTR_ERRMODE));

    dropTable($conn, "table1");
    dropTable($conn, "table2");
    dropTable($conn, "table3");

    // Should trigger exception
    $tsql = "SELECT * FROM dummy";
    $conn->exec($tsql);

    unset($conn);
} catch (PDOException $e) {
    print_r($e->getMessage());
    exit;
}


?>
--EXPECTREGEX--
int\(2\)
int\(2\)
int\(2\)
.*Invalid object name \'dummy\'\.
