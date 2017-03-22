--TEST--
Number of rows modified by the last statement executed
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

// Connect
$conn = sqlsrv_connect($serverName, $connectionInfo);
if( !$conn ) { die(print_r( sqlsrv_errors(), true)); }

// Create database
sqlsrv_query($conn,"CREATE DATABASE ". $dbName) ?: die();

// Create table
$stmt = sqlsrv_query($conn, "CREATE TABLE ".$tableName." (c1 VARCHAR(32))");
if( $stmt === false ) { die( print_r( sqlsrv_errors(), true )); }
sqlsrv_free_stmt( $stmt);

// Insert data
$query = "INSERT INTO ".$tableName.
	" VALUES ('Salmon'),('Butterfish'),('Cod'),('NULL'),('Crab')";
$stmt = sqlsrv_query($conn, $query);
if( $stmt === false ) { die( print_r( sqlsrv_errors(), true )); }
$res[] = sqlsrv_rows_affected($stmt);

// Update data
$query = "UPDATE ".$tableName." SET c1='Salmon' WHERE c1='Cod'";
$stmt = sqlsrv_query($conn, $query);
$res[] = sqlsrv_rows_affected($stmt);

// Update data
$query = "UPDATE ".$tableName." SET c1='Salmon' WHERE c1='NULL'";
$stmt = sqlsrv_query($conn, $query);
$res[] = sqlsrv_rows_affected($stmt);

// Update data
$query = "UPDATE ".$tableName." SET c1='Salmon' WHERE c1='NO_NAME'";
$stmt = sqlsrv_query($conn, $query);
$res[] = sqlsrv_rows_affected($stmt);

// Update data
$query = "UPDATE ".$tableName." SET c1='N/A'";
$stmt = sqlsrv_query($conn, $query);
$res[] = sqlsrv_rows_affected($stmt);

print_r($res);

// DROP database
$stmt = sqlsrv_query($conn,"DROP DATABASE ". $dbName);

sqlsrv_free_stmt( $stmt);
sqlsrv_close($conn);
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
