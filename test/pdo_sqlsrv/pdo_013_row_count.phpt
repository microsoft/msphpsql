--TEST--
Number of rows in a result set
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

$conn = new PDO( "sqlsrv:server=$serverName", "$username", "$password" );

// CREATE database
$conn->query("CREATE DATABASE ". $dbName) ?: die();

// Create table
$stmt = $conn->query("CREATE TABLE ".$tableName." (c1 VARCHAR(32))");
$stmt=null;

// Insert data
$query = "INSERT INTO ".$tableName." VALUES ('Salmon'),('Butterfish'),('Cod'),('NULL'),('Crab')";
$stmt = $conn->query($query);
$res[] = $stmt->rowCount();

// Update data
$query = "UPDATE ".$tableName." SET c1='Salmon' WHERE c1='Cod'";
$stmt = $conn->query($query);
$res[] = $stmt->rowCount();

// Update data
$query = "UPDATE ".$tableName." SET c1='Salmon' WHERE c1='NULL'";
$stmt = $conn->query($query);
$res[] = $stmt->rowCount();

// Update data
$query = "UPDATE ".$tableName." SET c1='Salmon' WHERE c1='NO_NAME'";
$stmt = $conn->query($query);
$res[] = $stmt->rowCount();

// Update data
$query = "UPDATE ".$tableName." SET c1='N/A'";
$stmt = $conn->query($query);
$res[] = $stmt->rowCount();

print_r($res);

// DROP database
$conn->query("DROP DATABASE ". $dbName) ?: die();

$stmt=null;
$conn=null;
print "Done"
?>
--EXPECT--
Array
(
    [0] => 5
    [1] => 1
    [2] => 1
    [3] => 0
    [4] => 5
)
Done
