--TEST--
prepare with emulate prepare and binding integer
--SKIPIF--

--FILE--
<?php
require('MsSetup.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", $uid, $pwd);

$tableName = "number_types";

$query = "IF OBJECT_ID('number_types') IS NOT NULL DROP TABLE [$tableName]";
$stmt = $conn->query($query);

$query = "CREATE TABLE [$tableName] (c1_decimal decimal, c2_money money, c3_float float)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (c1_decimal, c2_money, c3_float) VALUES (411.1, 131.11, 611.111)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (c1_decimal, c2_money, c3_float) VALUES (422.2222, 132.222, 622.22)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (c1_decimal, c2_money, c3_float) VALUES (433.333, 133.3333, 633.33333 )";
$stmt = $conn->query($query);

$query = "SELECT * FROM [$tableName] WHERE c3_float = :c3";

// prepare without emulate prepare
print_r("Prepare without emulate prepare:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => false));
$c3 = 611.111;
$stmt->bindParam(':c3', $c3);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//with emulate prepare and no bind param options
print_r("Prepare with emulate prepare and no bind param options:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$c3 = 611.111;
$stmt->bindParam(':c3', $c3);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//with emulate prepare and encoding SQLSRV_ENCODING_SYSTEM
print_r("Prepare with emulate prepare and SQLSRV_ENCODING_SYSTEM:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$c3 = 611.111;
$stmt->bindParam(':c3', $c3, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_SYSTEM);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//prepare with emulate prepare and encoding SQLSRV_ENCODING_UTF8
print_r("Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$c3 = 611.111;
$stmt->bindParam(':c3', $c3, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY
print_r("Prepare with emulate prepare and SQLSRV_ENCODING_BINARY:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$c3 = 611.111;
$stmt->bindParam(':c3', $c3, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);
if ($stmt->rowCount() == 0){
	print_r("No results for this query\n");
}

$stmt = null;
$conn=null;
?>

--EXPECT--
Prepare without emulate prepare:
Array
(
    [c1_decimal] => 411
    [c2_money] => 131.1100
    [c3_float] => 611.11099999999999
)
Prepare with emulate prepare and no bind param options:
Array
(
    [c1_decimal] => 411
    [c2_money] => 131.1100
    [c3_float] => 611.11099999999999
)
Prepare with emulate prepare and SQLSRV_ENCODING_SYSTEM:
Array
(
    [c1_decimal] => 411
    [c2_money] => 131.1100
    [c3_float] => 611.11099999999999
)
Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:
Array
(
    [c1_decimal] => 411
    [c2_money] => 131.1100
    [c3_float] => 611.11099999999999
)
Prepare with emulate prepare and SQLSRV_ENCODING_BINARY:
No results for this query