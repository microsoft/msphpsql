--TEST--
prepare with emulate prepare and binding integer
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('MsSetup.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", $uid, $pwd);

$tableName = "date_types";

$query = "IF OBJECT_ID('date_types') IS NOT NULL DROP TABLE [$tableName]";
$stmt = $conn->query($query);

$query = "CREATE TABLE [$tableName] (c1_datetime datetime, c2_nvarchar nvarchar(20))";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (c1_datetime, c2_nvarchar) VALUES ('2012-06-18 10:34:09', N'2012-06-18 10:34:09')";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (c1_datetime, c2_nvarchar) VALUES ('2008-11-11 13:23:44', N'2008-11-11 13:23:44')";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (c1_datetime, c2_nvarchar) VALUES ('2012-09-25 19:47:00', N'2012-09-25 19:47:00')";
$stmt = $conn->query($query);

$query = "SELECT * FROM [$tableName] WHERE c1_datetime = :c1";

// prepare without emulate prepare
print_r("Prepare without emulate prepare:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => false));
$c1 = '2012-09-25 19:47:00';
$stmt->bindParam(':c1', $c1);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//with emulate prepare and no bind param options
print_r("Prepare with emulate prepare and no bind param options:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$c1 = '2012-09-25 19:47:00';
$stmt->bindParam(':c1', $c1);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//with emulate prepare and encoding SQLSRV_ENCODING_SYSTEM
print_r("Prepare with emulate prepare and SQLSRV_ENCODING_SYSTEM:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$c1 = '2012-09-25 19:47:00';
$stmt->bindParam(':c1', $c1, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_SYSTEM);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//prepare with emulate prepare and encoding SQLSRV_ENCODING_UTF8
print_r("Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$c1 = '2012-09-25 19:47:00';
$stmt->bindParam(':c1', $c1, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY
print_r("Prepare with emulate prepare and SQLSRV_ENCODING_BINARY:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$c1 = '2012-09-25 19:47:00';
$stmt->bindParam(':c1', $c1, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
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