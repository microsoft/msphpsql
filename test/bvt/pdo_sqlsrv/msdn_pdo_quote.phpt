--TEST--
insert with quoted parameters
--SKIPIF--

--FILE--
<?php

require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = tempdb", "$uid", "$pwd");
$conn->exec("CREAtE TABLE Table1(col1 VARCHAR(15), col2 VARCHAR(15)) ");

$param = 'a \' g';
$param2 = $conn->quote( $param );

$query = "INSERT INTO Table1 VALUES( ?, '1' )";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param));

$query = "INSERT INTO Table1 VALUES( ?, ? )";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param, $param2));

$query = "SELECT * FROM Table1";
$stmt = $conn->query($query);
while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print_r( $row['col1'] ." was inserted\n" );
}

// revert the inserts
$query = "delete from Table1 where col1 = ?";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param));

$conn->exec("DROP TABLE Table1 ");
  
//free the statement and connection
$stmt=null;
$conn=null;
?>
--EXPECT--
a ' g was inserted
a ' g was inserted