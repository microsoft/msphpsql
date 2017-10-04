--TEST--
bind inout param with PDO::SQLSRV_ENCODING_BINARY
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $pdo = connect();

    $tbname = "my_table";
    createTable($pdo, $tbname, array("value" => "varchar(20)", "name" => "varchar(20)"));
    insertRow($pdo, $tbname, array( "value" => "Initial string", "name" => "name" ));

    $value = 'Some string value.';
    $name = 'name';

    $sql = "UPDATE my_table SET value = :value WHERE name = :name";
    $stmt = $pdo->prepare($sql);
    $stmt->bindParam(':value', $value, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->bindParam(':name', $name);
    $stmt->execute();

    $result = selectRow($pdo, $tbname, "PDO::FETCH_ASSOC");
    print_r($result);

    $stmt->closeCursor();
    dropTable($pdo, $tbname);
    unset($stmt);
    unset($pdo);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
Array
(
    [value] => Some string value.
    [name] => name
)
