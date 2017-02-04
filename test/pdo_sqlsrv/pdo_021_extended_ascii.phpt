--TEST--
Bind parameters VARCHAR(n) extended ASCII
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

// Connect
$conn = new PDO("sqlsrv:server=$serverName", $username, $password);

// CREATE database
$conn->query("CREATE DATABASE ". $dbName) ?: die();

// Create table
$sql = "CREATE TABLE $tableName (code CHAR(2), city VARCHAR(32))";
$stmt = $conn->query($sql);

// Insert data using bind parameters
$sql = "INSERT INTO $tableName VALUES (?,?)";

// First row 
$stmt = $conn->prepare($sql);
$params = array("FI","Järvenpää");
$stmt->execute($params);

// Second row
$params = array("DE","München");
$stmt->execute($params);

// Query, fetch
$sql = "SELECT * from $tableName";
$stmt = $conn->query($sql);
$data = $stmt->fetchAll();

// Print out
foreach ($data as $a)
echo $a[0] . "|" . $a[1] . "\n";

// DROP database
$conn->query("DROP DATABASE ". $dbName) ?: die();

// Close connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
FI|Järvenpää
DE|München
Done
