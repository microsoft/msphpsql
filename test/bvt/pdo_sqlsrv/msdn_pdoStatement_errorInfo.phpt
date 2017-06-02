--TEST--
reports the error info of a SQL statement with a mispelled table name
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");
$stmt = $conn->prepare('SELECT * FROM Person.Addressx');

$stmt->execute();
print_r ($stmt->errorInfo());

// free the statement and connection 
$stmt=null;
$conn=null;
?>
--EXPECTREGEX--
Array
\(
    \[0\] => 42S02
    \[1\] => 208
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid object name 'Person.Addressx'.
\)