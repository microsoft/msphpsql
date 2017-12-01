--TEST--
prepare with emulate prepare and binding integer
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once('MsCommon_mid-refactor.inc');
try {
    $conn = connect();

    $tableName = "fruit";
    createTable($conn, $tableName, array("name" => "varchar(max)", "calories" => "int"));

    insertRow($conn, $tableName, array("name" => "apple", "calories" => 150));
    insertRow($conn, $tableName, array("name" => "banana", "calories" => 175));
    insertRow($conn, $tableName, array("name" => "blueberry", "calories" => 1));

    $query = "SELECT * FROM [$tableName] WHERE calories = :cal";

    // prepare without emulate prepare
    print_r("Prepare without emulate prepare:\n");
    $options = array(PDO::ATTR_EMULATE_PREPARES => false);
    $stmt = $conn->prepare($query, $options);
    $cal = 1;
    $stmt->bindParam(':cal', $cal, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    // prepare with emulate prepare
    print_r("Prepare with emulate prepare and no bindParam options:\n");
    if (!isAEConnected()) {
        // emulate prepare is not supported for encrypted columns
        $options = array(PDO::ATTR_EMULATE_PREPARES => true);
    }
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':cal', $cal, PDO::PARAM_INT);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    if (!isAEConnected()) {
        // without emulate prepare, binding PARAM_INT with SQLSRV_ENCODING_SYSTEM is not allowed
        // thus the following will not be tested when Column Encryption is enabled

        $results = array();
        //prepare with emulate prepare and encoding SQLSRV_ENCODING_SYSTEM
        $stmt = $conn->prepare($query, $options);
        $stmt->bindParam(':cal', $cal, PDO::PARAM_INT, 0, PDO::SQLSRV_ENCODING_SYSTEM);
        $stmt->execute();
        $results["SYSTEM"] = $stmt->fetch(PDO::FETCH_ASSOC);

        //prepare with emulate prepare and encoding SQLSRV_ENCODING_UTF8
        $stmt = $conn->prepare($query, $options);
        $stmt->bindParam(':cal', $cal, PDO::PARAM_INT, 0, PDO::SQLSRV_ENCODING_UTF8);
        $stmt->execute();
        $results["UTF8"] = $stmt->fetch(PDO::FETCH_ASSOC);

        //prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY
        $stmt = $conn->prepare($query, $options);
        $stmt->bindParam(':cal', $cal, PDO::PARAM_INT, 0, PDO::SQLSRV_ENCODING_BINARY);
        $stmt->execute();
        $results["BINARY"] = $stmt->fetch(PDO::FETCH_ASSOC);

        foreach ($results as $key => $value) {
            if ($value['name'] != "blueberry" || $value['calories'] != 1) {
                echo "Failed to fetch when binding parameter with $key encoding.\n";
            }
        }
    }

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
