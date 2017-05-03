--TEST--
prepare with emulate prepare and binding varchar
--SKIPIF--

--FILE--
<?php
require('MsSetup.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", $uid, $pwd);

$tableName = "fruit";

$query = "IF OBJECT_ID('fruit') IS NOT NULL DROP TABLE [$tableName]";
$stmt = $conn->query($query);

$query = "CREATE TABLE [$tableName] (name varchar(max), calories int)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (name, calories) VALUES ('apple', 150)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (name, calories) VALUES ('banana', 175)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (name, calories) VALUES ('blueberry', 1)";
$stmt = $conn->query($query);

$query = "SELECT * FROM [$tableName] WHERE name = :name";

//prepare without emulate prepare
print_r("Prepare without emulate prepare:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => false));
$name = 'blueberry';
$stmt->bindParam(':name', $name);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//prepare with emulate prepare and no bind param options
print_r("Prepare with emulate prepare and no bindParam options:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$name = 'blueberry';
$stmt->bindParam(':name', $name);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//prepare with emulate prepare and encoding SQLSRV_ENCODING_SYSTEM
print_r("Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$name = 'blueberry';
$stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_SYSTEM);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//prepare with emulate prepare and encoding SQLSRV_ENCODING_UTF8
print_r("Prepare with emulate prepare and and SQLSRV_ENCODING_SYSTEM:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$name = 'blueberry';
$stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY
print_r("Prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$name = 'blueberry';
$stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

$stmt = null;
$conn=null;
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