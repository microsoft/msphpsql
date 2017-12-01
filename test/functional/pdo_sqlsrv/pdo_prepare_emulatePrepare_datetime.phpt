--TEST--
prepare with emulate prepare and binding integer
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    $tableName = "date_types";
    createTable($conn, $tableName, array("c1_datetime" => "datetime", "c2_nvarchar" => "nvarchar(20)"));

    insertRow($conn, $tableName, array("c1_datetime" => "2012-06-18 10:34:09", "c2_nvarchar" => "2012-06-18 10:34:09"));
    insertRow($conn, $tableName, array("c1_datetime" => "2008-11-11 13:23:44", "c2_nvarchar" => "2008-11-11 13:23:44"));
    insertRow($conn, $tableName, array("c1_datetime" => "2012-09-25 19:47:00", "c2_nvarchar" => "2012-09-25 19:47:00"));

    $query = "SELECT * FROM [$tableName] WHERE c1_datetime = :c1";

    // prepare without emulate prepare
    print_r("Prepare without emulate prepare:\n");
    $options = array(PDO::ATTR_EMULATE_PREPARES => false);
    $stmt = $conn->prepare($query, $options);
    $c1 = '2012-09-25 19:47:00';
    $stmt->bindParam(':c1', $c1);
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    print_r($row);

    //with emulate prepare and no bind param options
    if (!isAEConnected()) {
        // emulate prepare is not supported in encrypted columns
        $options = array(PDO::ATTR_EMULATE_PREPARES => true);
    }
    print_r("Prepare with emulate prepare and no bind param options:\n");
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
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo[2]);
}
?>

--EXPECT--
Prepare without emulate prepare:
Array
(
    [c1_datetime] => 2012-09-25 19:47:00.000
    [c2_nvarchar] => 2012-09-25 19:47:00
)
Prepare with emulate prepare and no bind param options:
Array
(
    [c1_datetime] => 2012-09-25 19:47:00.000
    [c2_nvarchar] => 2012-09-25 19:47:00
)
Prepare with emulate prepare and SQLSRV_ENCODING_SYSTEM:
Array
(
    [c1_datetime] => 2012-09-25 19:47:00.000
    [c2_nvarchar] => 2012-09-25 19:47:00
)
Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:
Array
(
    [c1_datetime] => 2012-09-25 19:47:00.000
    [c2_nvarchar] => 2012-09-25 19:47:00
)
Prepare with emulate prepare and SQLSRV_ENCODING_BINARY:
No results for this query
