--TEST--
a variable bound to a column in a result set
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$query = "SELECT Title, FirstName, EmailPromotion FROM Person.Person where LastName = 'Estes'";
$stmt = $conn->prepare($query);
$stmt->execute();

$stmt->bindColumn('EmailPromotion', $emailpromo);
while ( $row = $stmt->fetch( PDO::FETCH_BOUND ) ){
   echo "EmailPromotion: $emailpromo\n";
}

//free the statement and connection 
$stmt=null;
$conn=null;
?>
--EXPECT--
EmailPromotion: 2