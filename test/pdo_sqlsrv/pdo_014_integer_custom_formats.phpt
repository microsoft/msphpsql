--TEST--
Number MAX_INT to string with custom formats
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

/* Sample number MAX_INT */
$sample = 2*(2**30-1)+1;
var_dump ($sample);

/* Connect */
$conn = new PDO("sqlsrv:server=$server; database=$databaseName", $uid, $pwd);

// Create table
$tableName = '#testCustomFormats';
$query = "CREATE TABLE $tableName (col1 INT)";
$stmt = $conn->exec($query);

// Query number with custom format
$query ="SELECT CAST($sample as varchar) + '.00'";
$stmt = $conn->query($query);
$data = $stmt->fetchColumn();
var_dump ($data);

// Insert data using bind parameters
$query = "INSERT INTO $tableName VALUES(:p0)";
$stmt = $conn->prepare($query);
$stmt->bindValue(':p0', $sample, PDO::PARAM_INT);
$stmt->execute();

// Fetching. Prepare with client buffered cursor
$query = "SELECT TOP 1 cast(col1 as varchar) + '.00 EUR' FROM $tableName";
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

//Free the statement and connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
int(2147483647)
string(13) "2147483647.00"
string(17) "2147483647.00 EUR"
Done
