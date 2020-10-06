--TEST--
reports the error info of a SQL statement with a mispelled table name
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_SILENT);
$stmt = $conn->prepare('SELECT * FROM Person.Addressx');

$stmt->execute();
print_r ($stmt->errorInfo());

// free the statement and connection 
unset($stmt);
unset($conn);
?>
--EXPECTREGEX--
Array
\(
    \[0\] => 42S02
    \[1\] => 208
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid object name 'Person.Addressx'\.
    \[3\] => 42000
    \[4\] => 8180
    \[5\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Statement\(s\) could not be prepared\.
\)