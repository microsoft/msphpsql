--TEST--
Retrieve error information; supplied values does not match table definition
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

// Connect
$conn = new PDO("sqlsrv:server=$serverName", $username, $password);

// CREATE database
$conn->query("CREATE DATABASE ". $dbName) ?: die();

// Create table
$sql = "CREATE TABLE $tableName (code INT)";
$stmt = $conn->query($sql);

// Insert data using bind parameters
// Number of supplied values does not match table definition
$sql = "INSERT INTO $tableName VALUES (?,?)";
$stmt = $conn->prepare($sql);
$params = array(2010,"London");

// SQL statement has an error, which is then reported
$stmt->execute($params);
print_r($stmt->errorInfo());

// DROP database
$conn->query("DROP DATABASE ". $dbName) ?: die();

// Close connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
Array
(
    [0] => 21S01
    [1] => 213
    [2] => [Microsoft][ODBC Driver 13 for SQL Server][SQL Server]Column name or number of supplied values does not match table definition.
)
Done
