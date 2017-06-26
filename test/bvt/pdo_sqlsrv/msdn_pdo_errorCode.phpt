--TEST--
reports the error code of querying a misspelled column
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:Server=$server ; Database = $databaseName ", "$uid", "$pwd");
$query = "SELECT * FROM Person.Address where Cityx = 'Essen'";

$conn->query($query);
print $conn->errorCode();

//free the connection
$conn=null;
?>
--EXPECT--
42S22