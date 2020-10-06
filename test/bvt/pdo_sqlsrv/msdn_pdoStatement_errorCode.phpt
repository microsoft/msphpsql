--TEST--
shows the error code of a SQL query with a mispelled table
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server; Database = $databaseName", "$uid", "$pwd");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
$stmt = $conn->prepare('SELECT * FROM Person.Addressx');

$stmt->execute();
echo "Error Code: ";
print $stmt->errorCode();

// free the statement and connection 
unset($stmt);
unset($conn);
?>
--EXPECT--
Error Code: 42S02