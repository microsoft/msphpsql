--TEST--
Insert with quoted parameters
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");

// Connect
$conn = new PDO( "sqlsrv:server=$server; database=$databaseName", "$uid", "$pwd" );

$param = 'a \' g';
$param2 = $conn->quote( $param );

// Create a temporary table
$tableName = '#tmpTable';
$query = "CREATE TABLE $tableName (col1 VARCHAR(10), col2 VARCHAR(20))";
$stmt = $conn->exec($query);
if( $stmt === false ) { die(); }

// Inserd data
$query = "INSERT INTO $tableName VALUES( ?, '1' )";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param));

// Inserd data
$query = "INSERT INTO $tableName VALUES( ?, ? )";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param, $param2));

// Query
$query = "SELECT * FROM $tableName";
$stmt = $conn->query($query);
while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print_r( $row['col1'] ." was inserted\n" );
}

// Revert the inserts
$query = "delete from $tableName where col1 = ?";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param));

//free the statement and connection
$stmt=null;
$conn=null;
?>
--EXPECT--
a ' g was inserted
a ' g was inserted

