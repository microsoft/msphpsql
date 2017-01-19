--TEST--
Number MAX_INT to string with custom formats, see pdo_014. Pooling enabled.
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

$pooling = true;

/* Sample number MAX_INT */
$sample = 2**31-1;
var_dump ($sample);

/* Connect + create a new pool */
$conn0 = new PDO("sqlsrv:server=$serverName;ConnectionPooling=$pooling", "sa", "Moonshine4me");
$conn0->query("select 1");
$conn0 = null;

/* Connect */
$conn = new PDO("sqlsrv:server=$serverName;ConnectionPooling=$pooling", "sa", "Moonshine4me");

// Create database
$conn->query("CREATE DATABASE $dbName") ?: die();

// Create table
$query = "CREATE TABLE $tableName (col1 INT)";
$stmt = $conn->query($query);

// Query number with custom format
$query ="SELECT FORMAT($sample,'#,0.00')";
$stmt = $conn->query($query);
$data = $stmt->fetchColumn();
var_dump ($data);

// Insert data using bind parameters
$query = "INSERT INTO $tableName VALUES(:p0)";
$stmt = $conn->prepare($query);
$stmt->bindValue(':p0', $sample, PDO::PARAM_INT);
$stmt->execute();

// Fetching. Prepare with client buffered cursor
$query = "SELECT TOP 1 FORMAT(col1,'#,0.00E') FROM $tableName";
// $conn->setAttribute(PDO::ATTR_STRINGIFY_FETCHES, false);
$stmt = $conn->prepare($query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, 
		PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
$stmt->execute();
$value = $stmt->fetchColumn();
var_dump ($value);

// DROP database
$conn->query("DROP DATABASE ". $dbName) ?: die();
  
//Free the statement and connection
$stmt = null;
$conn = null;

print "Done";
?>

--EXPECT--
int(2147483647)
string(16) "2,147,483,647.00"
string(17) "2,147,483,647.00E"
Done
