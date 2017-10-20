--TEST--
Insert into decimal columns with inputs of various scale and precision
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();

    $tableName = "decimal_table";

    createTable($conn, $tableName, array("c1_decimal0" => "decimal", "c2_decimal4" => "decimal(19,4)"));

    insertRow($conn, $tableName, array("c1_decimal0" => 0.9, "c2_decimal4" => 0.9));
    insertRow($conn, $tableName, array("c1_decimal0" => 9.9, "c2_decimal4" => 9.9));
    insertRow($conn, $tableName, array("c1_decimal0" => 999.999, "c2_decimal4" => 999.999));
    insertRow($conn, $tableName, array("c1_decimal0" => 99999.99999, "c2_decimal4" => 99999.99999));
    
    $query = "SELECT * FROM $tableName";
    $stmt = $conn->query($query);
    $row = $stmt->fetchAll(PDO::FETCH_ASSOC);
    var_dump($row);

    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}

?>
--EXPECT--
array(4) {
  [0]=>
  array(2) {
    ["c1_decimal0"]=>
    string(1) "1"
    ["c2_decimal4"]=>
    string(5) ".9000"
  }
  [1]=>
  array(2) {
    ["c1_decimal0"]=>
    string(2) "10"
    ["c2_decimal4"]=>
    string(6) "9.9000"
  }
  [2]=>
  array(2) {
    ["c1_decimal0"]=>
    string(4) "1000"
    ["c2_decimal4"]=>
    string(8) "999.9990"
  }
  [3]=>
  array(2) {
    ["c1_decimal0"]=>
    string(6) "100000"
    ["c2_decimal4"]=>
    string(11) "100000.0000"
  }
}