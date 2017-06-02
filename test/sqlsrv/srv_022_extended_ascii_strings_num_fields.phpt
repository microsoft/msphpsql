--TEST--
Query with extended ASCII column names, sqlsrv_num_fields()
--DESCRIPTION--
Create a temporary table with column names that contain extended ASCII characters. Get number of fields 
using sqlsrv_num_fields.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

// Connect
$conn = ConnectUTF8();
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Create table
$tableName = '#srv_022test';
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (Cité NVARCHAR(10), Année SMALLINT)");
if( $stmt === false ) { die( print_r( sqlsrv_errors(), true )); }
sqlsrv_free_stmt( $stmt);

// Insert data
$sql1 = "INSERT INTO $tableName VALUES ('Paris', 1911)";
$stmt1 = sqlsrv_query( $conn, $sql1);
sqlsrv_free_stmt( $stmt1);

// Insert more data
$sql2 = "INSERT INTO $tableName VALUES ('London', 2012), ('Berlin', 1990)";
$stmt2 = sqlsrv_query($conn, $sql2);
sqlsrv_free_stmt( $stmt2);

// Query
$sql = "SELECT * FROM $tableName";
$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) { die( print_r( sqlsrv_errors(), true) ); }

// Get number of fields
echo sqlsrv_num_fields($stmt)."\n";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
2
Done
