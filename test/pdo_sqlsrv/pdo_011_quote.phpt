--TEST--
Insert with quoted parameters
--SKIPIF--

--FILE--
<?php
require_once("autonomous_setup.php");

// Connect
$conn = new PDO( "sqlsrv:server=$serverName", "$username", "$password" );

$param = 'a \' g';
$param2 = $conn->quote( $param );

// CREATE database
$conn->query("CREATE DATABASE ". $dbName) ?: die();

// Create table
$query = "CREATE TABLE ".$tableName." (col1 VARCHAR(10), col2 VARCHAR(20))";
$stmt = $conn->query($query);
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

// DROP database
$conn->query("DROP DATABASE ". $dbName) ?: die();

//free the statement and connection
$stmt=null;
$conn=null;
?>
--EXPECT--
a ' g was inserted
a ' g was inserted

