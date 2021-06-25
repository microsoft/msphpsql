--TEST--
insert with quoted parameters
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require('connect.inc');
$conn = new PDO("sqlsrv:Server=$server; Database = $databaseName", $uid, $pwd);

$tableName = "pdoQuote";
dropTable($conn, $tableName);

$conn->exec("CREATE TABLE $tableName(col1 VARCHAR(15), col2 VARCHAR(15)) ");

$param = 'a \' g';
$param2 = $conn->quote( $param );

$query = "INSERT INTO $tableName VALUES( ?, '1' )";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param));

$query = "INSERT INTO $tableName VALUES( ?, ? )";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param, $param2));

$query = "SELECT * FROM $tableName";
$stmt = $conn->query($query);
while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print_r( $row['col1'] ." was inserted\n" );
}

// revert the inserts
$query = "DELETE FROM $tableName WHERE col1 = ?";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param));

dropTable($conn, $tableName, false);

//free the statement and connection
unset($stmt);
unset($conn);
?>
--EXPECT--
a ' g was inserted
a ' g was inserted