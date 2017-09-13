--TEST--
Retrieve error information; supplied values does not match table definition
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

// Connect
$conn = new PDO("sqlsrv:server=$server; database=$databaseName", $uid, $pwd);

// Create table
$tableName = '#pdo_040test';
$sql = "CREATE TABLE $tableName (code INT)";
$stmt = $conn->exec($sql);

// Insert data using bind parameters
// Number of supplied values does not match table definition
$sql = "INSERT INTO $tableName VALUES (?,?)";
$stmt = $conn->prepare($sql);
$params = array(2010,"London");

// SQL statement has an error, which is then reported
$stmt->execute($params);
print_r($stmt->errorInfo());

// Close connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECTREGEX--
Array
\(
    \[0\] => 21S01
    \[1\] => 213
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Column name or number of supplied values does not match table definition\.
\)
Done
