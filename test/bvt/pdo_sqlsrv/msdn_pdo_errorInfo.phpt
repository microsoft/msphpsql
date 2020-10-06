--TEST--
reports the error info of querying a misspelled column
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
echo "\n";
print_r ($conn->errorInfo());

//free the connection
$conn=null;
?>
--EXPECTREGEX--
42S22
Array
\(
    \[0\] => 42S22
    \[1\] => 207
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid column name 'Cityx'.
    \[3\] => 42000
    \[4\] => 8180
    \[5\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Statement\(s\) could not be prepared\.
\)