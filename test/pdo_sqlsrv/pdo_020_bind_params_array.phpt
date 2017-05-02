--TEST--
Bind parameters using an array
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

// Connect
$conn = new PDO("sqlsrv:server=$server; database=$databaseName", $uid, $pwd);

// Create table
$tableName = '#bindParams';
$sql = "CREATE TABLE $tableName (ID TINYINT, SID CHAR(5))";
$stmt = $conn->exec($sql);

// Insert data using bind parameters
$sql = "INSERT INTO $tableName VALUES (?,?)";
for($t=100; $t<103; $t++) {
	$stmt = $conn->prepare($sql);
	$ts = substr(sha1($t),0,5);
	$params = array($t,$ts);
	$stmt->execute($params);
}

// Query, but do not fetch
$sql = "SELECT * from $tableName";
$stmt = $conn->query($sql);

// Insert duplicate row, ID = 100
$t = 100;
$sql = "INSERT INTO $tableName VALUES (?,?)";
$stmt1 = $conn->prepare($sql);
$ts = substr(sha1($t),0,5);
$params = array($t,$ts);
$stmt1->execute($params);

// Fetch. The result set should not contain duplicates
$data = $stmt->fetchAll();
foreach ($data as $a)
echo $a['ID'] . "|" . $a['SID'] . "\n";

// Close connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
100|310b8
101|dbc0f
102|c8306
Done
