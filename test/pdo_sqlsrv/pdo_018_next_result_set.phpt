--TEST--
Moves the cursor to the next result set
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

/* Connect */
$conn = new PDO("sqlsrv:server=$server; database=$databaseName", $uid, $pwd);

// Create table
$tableName = '#testResultSet';
$sql = "CREATE TABLE $tableName (c1 INT, c2 VARCHAR(40))";
$stmt = $conn->exec($sql);

// Insert data using bind parameters
$sql = "INSERT INTO $tableName VALUES (?,?)";
for($t=200; $t<220; $t++) {
	$stmt = $conn->prepare($sql);
	$stmt->bindParam(1, $t);
	$ts = sha1($t);
	$stmt->bindParam(2, $ts);
	$stmt->execute();
}

// Fetch, get data and move the cursor to the next result set
$sql = "SELECT * from $tableName WHERE c1 = '204' OR c1 = '210'; 
		SELECT Top 3 * FROM $tableName ORDER BY c1 DESC";
$stmt = $conn->query($sql);
$data1 = $stmt->fetchAll(PDO::FETCH_ASSOC);
$stmt->nextRowset();
$data2 = $stmt->fetchAll(PDO::FETCH_NUM);

// Array: FETCH_ASSOC
foreach ($data1 as $a)
echo $a['c1'] . "|" . $a['c2'] . "\n";

// Array: FETCH_NUM
foreach ($data2 as $a)
echo $a[0] . "|" . $a[1] . "\n";

// Close connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
204|1cc641954099c249e0e4ef0402da3fd0364d95f0
210|135debd4837026bf06c7bfc5d1e0c6a31611af1d
219|c0ba17c23a26ff8c314478bc69f30963a6e4a754
218|3d5bdf107de596ce77e8ce48a61b585f52bbb61d
217|49e3d046636e06b2d82ee046db8e6eb9a2e11e16
Done
