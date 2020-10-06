--TEST--
reports the error code of querying a misspelled column
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:Server=$server ; Database = $databaseName ", "$uid", "$pwd");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
$query = "SELECT * FROM Person.Address where Cityx = 'Essen'";

$conn->query($query);
print $conn->errorCode();

//free the connection
unset($conn);
?>
--EXPECT--
42S22