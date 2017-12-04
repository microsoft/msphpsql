--TEST--
prepare with emulate prepare and binding integer
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    $tableName = "number_types";
    if (!isAEConnected()) {
        createTable($conn, $tableName, array("c1_decimal" => "decimal", "c2_money" => "money", "c3_float" => "float"));
    } else {
        // money is not supported for column encryption, use decimal(19,4) instead
        createTable($conn, $tableName, array("c1_decimal" => "decimal", "c2_money" => "decimal(19,4)", "c3_float" => "float"));
    }

    insertRow($conn, $tableName, array("c1_decimal" => 411.1, "c2_money" => 131.11, "c3_float" => 611.111));
    insertRow($conn, $tableName, array("c1_decimal" => 422.2222, "c2_money" => 132.222, "c3_float" => 622.22));
    insertRow($conn, $tableName, array("c1_decimal" => 433.333, "c2_money" => 133.3333, "c3_float" => 633.33333));

    $query = "SELECT * FROM [$tableName] WHERE c1_decimal = :c1";

    // prepare without emulate prepare
    print_r("Prepare without emulate prepare:\n");
    $options = array(PDO::ATTR_EMULATE_PREPARES => false);
    $stmt = $conn->prepare($query, $options);
    $c1 = 422.2222;
    $stmt->bindParam(':c1', $c1);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //with emulate prepare and no bind param options
    print_r("Prepare with emulate prepare and no bind param options:\n");
    if (!isAEConnected()) {
        // emulate prepare is not supported for encrypted columns
        $options = array(PDO::ATTR_EMULATE_PREPARES => true);
    }
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':c1', $c1);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //with emulate prepare and encoding SQLSRV_ENCODING_SYSTEM
    print_r("Prepare with emulate prepare and SQLSRV_ENCODING_SYSTEM:\n");
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':c1', $c1, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_SYSTEM);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //prepare with emulate prepare and encoding SQLSRV_ENCODING_UTF8
    print_r("Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:\n");
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':c1', $c1, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY
    print_r("Prepare with emulate prepare and SQLSRV_ENCODING_BINARY:\n");
    $stmt = $conn->prepare($query, $options);
    $stmt->bindParam(':c1', $c1, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);
    if ($stmt->rowCount() == 0) {
        print_r("No results for this query\n");
    }

    dropTable($conn, $tableName);
    unset($conn);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
Prepare without emulate prepare:
Array
(
    [c1_decimal] => 422
    [c2_money] => 132.2220
    [c3_float] => 622.22000000000003
)
Prepare with emulate prepare and no bind param options:
Array
(
    [c1_decimal] => 422
    [c2_money] => 132.2220
    [c3_float] => 622.22000000000003
)
Prepare with emulate prepare and SQLSRV_ENCODING_SYSTEM:
Array
(
    [c1_decimal] => 422
    [c2_money] => 132.2220
    [c3_float] => 622.22000000000003
)
Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:
Array
(
    [c1_decimal] => 422
    [c2_money] => 132.2220
    [c3_float] => 622.22000000000003
)
Prepare with emulate prepare and SQLSRV_ENCODING_BINARY:
No results for this query
