--TEST--
shows the error code of a SQL query with a mispelled table
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server; Database = $databaseName", "$uid", "$pwd");
$stmt = $conn->prepare('SELECT * FROM Person.Addressx');

$stmt->execute();
echo "Error Code: ";
print $stmt->errorCode();

// free the statement and connection 
$stmt=null;
$conn=null;
?>
--EXPECT--
Error Code: 42S02