--TEST--
prepare with emulate prepare and binding varchar
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();

    $tableName = "fruit";
    createTable($conn, $tableName, array("name" => "varchar(max)", "calories" => "int"));

    insertRow($conn, $tableName, array("name" => "apple", "calories" => 150));
    insertRow($conn, $tableName, array("name" => "banana", "calories" => 175));
    insertRow($conn, $tableName, array("name" => "blueberry", "calories" => 1));

    $query = "SELECT * FROM [$tableName] WHERE name = :name";

    //prepare without emulate prepare
    print_r("Prepare without emulate prepare:\n");
    $options = array(PDO::ATTR_EMULATE_PREPARES => false);
    $stmt = $conn->prepare($query, $options);
    $name = 'blueberry';
    $stmt->bindParam(':name', $name);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //prepare with emulate prepare and no bind param options
    print_r("Prepare with emulate prepare and no bindParam options:\n");
    if (!isAEConnected()) {
        $options = array(PDO::ATTR_EMULATE_PREPARES => true);
    }
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':name', $name);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //prepare with emulate prepare and encoding SQLSRV_ENCODING_SYSTEM
    print_r("Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:\n");
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_SYSTEM);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //prepare with emulate prepare and encoding SQLSRV_ENCODING_UTF8
    print_r("Prepare with emulate prepare and and SQLSRV_ENCODING_SYSTEM:\n");
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY
    print_r("Prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY:\n");
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
Prepare without emulate prepare:
Array
(
    [name] => blueberry
    [calories] => 1
)
Prepare with emulate prepare and no bindParam options:
Array
(
    [name] => blueberry
    [calories] => 1
)
Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:
Array
(
    [name] => blueberry
    [calories] => 1
)
Prepare with emulate prepare and and SQLSRV_ENCODING_SYSTEM:
Array
(
    [name] => blueberry
    [calories] => 1
)
Prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY:
Array
(
    [name] => blueberry
    [calories] => 1
)
