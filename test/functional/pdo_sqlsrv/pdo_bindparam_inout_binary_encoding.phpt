--TEST--
bind inout param with PDO::SQLSRV_ENCODING_BINARY
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('MsSetup.inc');
$pdo = new PDO("sqlsrv:Server=$server ; Database = $databaseName ", $uid, $pwd);

$sql = "DROP TABLE my_table";
$stmt = $pdo->query($sql);
$stmt = null;

$sql = "CREATE TABLE my_table (value varchar(20), name varchar(20))";
$stmt = $pdo->query($sql);
$stmt = null;

$sql = "INSERT INTO my_table (value, name) VALUES ('Initial string', 'name')";
$stmt = $pdo->query($sql);
$stmt = null;

$value = 'Some string value.';
$name = 'name';

$sql = "UPDATE my_table SET value = :value WHERE name = :name";

$stmt = $pdo->prepare($sql);

$stmt->bindParam(':value', $value, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
$stmt->bindParam(':name', $name);

$stmt->execute();
$stmt = null;

$sql = "SELECT * FROM my_table";
$stmt = $pdo->query($sql);
$result = $stmt->fetch(PDO::FETCH_ASSOC);
print_r($result);

$stmt->closeCursor();
$pdo = null;
?>
--EXPECT--
Array
(
    [value] => Some string value.
    [name] => name
)