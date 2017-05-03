--TEST--
prepare with emulate prepare and binding uft8 characters
--SKIPIF--

--FILE--
<?php
require('MsSetup.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", $uid, $pwd);
//$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
//$conn->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 1 );

$tableName = "users";

$query = "IF OBJECT_ID('users') IS NOT NULL DROP TABLE [$tableName]";
$stmt = $conn->query($query);

$query = "CREATE TABLE [$tableName] (name nvarchar(max), status int, age int)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (name, status, age) VALUES (N'Belle', 1, 34)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (name, status, age) VALUES (N'Абрам', 1, 40)";
$stmt = $conn->query($query);

$query = "INSERT INTO [$tableName] (name, status, age) VALUES (N'가각', 1, 30)";
$stmt = $conn->query($query);

$name = "가각";

$query = "SELECT * FROM [$tableName] WHERE name = :name AND status = 1";

//without emulate prepare
print_r("Prepare without emulate prepare:\n");
$stmt = $conn->prepare($query);
$stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
$stmt->execute();
$row = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($row);

//with emulate prepare and no bind param options
print_r("Prepare with emulate prepare and no bindParam options:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$stmt->bindParam(':name', $name );
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);
if ($stmt->rowCount() == 0){
	print_r("No results for this query\n");
}

//with emulate prepare and SQLSRV_ENCODING_UTF8
print_r("Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_UTF8);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);

//with emulate prepare and SQLSRV_ENCODING_SYSTEM
print_r("Prepare with emulate prepare and and SQLSRV_ENCODING_SYSTEM:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_SYSTEM);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);
if ($stmt->rowCount() == 0){
	print_r("No results for this query\n");
}

//with emulate prepare and encoding SQLSRV_ENCODING_BINARY
print_r("Prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY:\n");
$stmt = $conn->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
$stmt->bindParam(':name', $name, PDO::PARAM_STR, 0, PDO::SQLSRV_ENCODING_BINARY);
$stmt->execute();
$row = $stmt->fetch( PDO::FETCH_ASSOC );
print_r($row);
if ($stmt->rowCount() == 0){
	print_r("No results for this query\n");
}

//$query = "DROP TABLE [$tableName]";
//$stmt = $conn->query($query);

$stmt = null;
$conn=null;
?>

--EXPECT--
Prepare without emulate prepare:
Array
(
    [name] => 가각
    [status] => 1
    [age] => 30
)
Prepare with emulate prepare and no bindParam options:
No results for this query
Prepare with emulate prepare and SQLSRV_ENCODING_UTF8:
Array
(
    [name] => 가각
    [status] => 1
    [age] => 30
)
Prepare with emulate prepare and and SQLSRV_ENCODING_SYSTEM:
No results for this query
Prepare with emulate prepare and encoding SQLSRV_ENCODING_BINARY:
No results for this query