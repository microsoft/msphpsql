--TEST--
Bind values with PDO::PARAM_BOOL
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

// Sample numbers
$sample = array([true, false],[-12, 0x2A],[0.00, NULL]);

// Connect
$conn_ops[PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE] = FALSE;
$conn = new PDO("sqlsrv:server=$serverName", $username, $password, $conn_ops);

// CREATE database
$conn->query("CREATE DATABASE ". $dbName) ?: die();

// Create table
$sql = "CREATE TABLE $tableName (c1 INT, c2 BIT)";
$stmt = $conn->query($sql);

// Insert data using bind parameters
$sql = "INSERT INTO $tableName VALUES (:v1, :v2)";
foreach ( $sample as $s) {
$stmt = $conn->prepare($sql);
$stmt->bindValue(':v1', $s[0], PDO::PARAM_BOOL);
$stmt->bindValue(':v2', $s[1], PDO::PARAM_BOOL);
$stmt->execute();
}

// Get data
$sql = "SELECT * FROM $tableName";
$stmt = $conn->query($sql);
$row = $stmt->fetchAll(PDO::FETCH_NUM);

// Print out
for($i=0; $i<$stmt->rowCount(); $i++)
	var_dump($row[$i]);

// DROP database
$conn->query("DROP DATABASE ". $dbName) ?: die();

// Close connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
array(2) {
  [0]=>
  string(1) "1"
  [1]=>
  string(1) "0"
}
array(2) {
  [0]=>
  string(1) "1"
  [1]=>
  string(1) "1"
}
array(2) {
  [0]=>
  string(1) "0"
  [1]=>
  NULL
}
Done
