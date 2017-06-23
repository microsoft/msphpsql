--TEST--
Bind integer parameters; allow fetch numeric types.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

/* Sample numbers MIN_INT, MAX_INT */
$sample = array(-2**31, 2**31-1);

/* Connect */
$conn_ops['pdo'][PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE] = TRUE;
$conn = new PDO("sqlsrv:server=$server; database=$databaseName", $uid, $pwd, $conn_ops['pdo']);

// Create table
$tableName = '#testPDO016';
$sql = "CREATE TABLE $tableName (c1 INT, c2 INT)";
$stmt = $conn->exec($sql);

// Insert data using bind parameters
$sql = "INSERT INTO $tableName VALUES (:num1, :num2)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(':num1', $sample[0], PDO::PARAM_INT);
$stmt->bindParam(':num2', $sample[1], PDO::PARAM_INT);
$stmt->execute();

// Fetch, get data
$sql = "SELECT * FROM $tableName";
$stmt = $conn->query($sql);
$row = $stmt->fetch(PDO::FETCH_NUM);
var_dump ($row);

// Close connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
array(2) {
  [0]=>
  int(-2147483648)
  [1]=>
  int(2147483647)
}
Done
