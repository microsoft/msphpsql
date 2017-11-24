--TEST--
Insert into decimal columns with inputs of various scale and precision
--SKIPIF--

--FILE--
<?php
require_once("MsHelper.inc");

$conn = AE\connect();

$tableName = "decimal_table";
AE\createTable($conn, $tableName, array(new AE\ColumnMeta("decimal", "c1_decimal0"), new AE\ColumnMeta("decimal(19,4)", "c2_decimal4")));

AE\insertRow($conn, $tableName, array("c1_decimal0" => 0.9, "c2_decimal4" => 0.9));
AE\insertRow($conn, $tableName, array("c1_decimal0" => 9.9, "c2_decimal4" => 9.9));
AE\insertRow($conn, $tableName, array("c1_decimal0" => 999.999, "c2_decimal4" => 999.999));
AE\insertRow($conn, $tableName, array("c1_decimal0" => 99999.99999, "c2_decimal4" => 99999.99999));
  
$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $query);
while (($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) != NULL) {
    var_dump($row);
}

dropTable($conn, $tableName);
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
array(2) {
  ["c1_decimal0"]=>
  string(1) "1"
  ["c2_decimal4"]=>
  string(5) ".9000"
}
array(2) {
  ["c1_decimal0"]=>
  string(2) "10"
  ["c2_decimal4"]=>
  string(6) "9.9000"
}
array(2) {
  ["c1_decimal0"]=>
  string(4) "1000"
  ["c2_decimal4"]=>
  string(8) "999.9990"
}
array(2) {
  ["c1_decimal0"]=>
  string(6) "100000"
  ["c2_decimal4"]=>
  string(11) "100000.0000"
}