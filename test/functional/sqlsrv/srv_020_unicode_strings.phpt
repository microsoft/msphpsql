--TEST--
Query non-ascii strings: sqlsrv_fetch_array
--DESCRIPTION--
Test sqlsrv_fetch_array() with non-ASCII values
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

// Connect
$conn = Connect();
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Create table
$tableName = '#srv_020test';
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (FirstName VARCHAR(10), LastName NVARCHAR(20), Age INT)");
if( $stmt === false ) { die( print_r( sqlsrv_errors(), true )); }
sqlsrv_free_stmt( $stmt);

// Insert data
$sql = "INSERT INTO $tableName VALUES ('John', 'Doe', 30)";
$stmt = sqlsrv_query( $conn, $sql);
sqlsrv_free_stmt( $stmt);

$sql = "INSERT INTO $tableName VALUES ('Nhoj', N'Eoduard', -3), ('Joe', N' I❤PHP', 2016)";
$stmt = sqlsrv_query($conn, $sql);
sqlsrv_free_stmt( $stmt);

// Query and print out
$sql = "SELECT FirstName, LastName, Age FROM $tableName";
$stmt = sqlsrv_query( $conn, $sql );
if( $stmt === false) { die( print_r( sqlsrv_errors(), true) ); }

while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC) ) {
      printf("%s %s %d\n",$row[0], $row[1], $row[2]);
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
John Doe 30
Nhoj Eoduard -3
Joe  I❤PHP 2016
Done
