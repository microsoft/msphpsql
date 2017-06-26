--TEST--
Executes a statement
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$query = "select * from Person.ContactType";
$stmt = $conn->prepare( $query );
$stmt->execute();

while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print "$row[Name]\n";
}

echo "\n";
$param = "Owner";
$query = "select * from Person.ContactType where name = ?";
$stmt = $conn->prepare( $query );
$stmt->execute(array($param));

while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print "$row[Name]\n";
}

// free the statement and connection 
$stmt=null;
$conn=null;
?>
--EXPECT--
Accounting Manager
Assistant Sales Agent
Assistant Sales Representative
Coordinator Foreign Markets
Export Administrator
International Marketing Manager
Marketing Assistant
Marketing Manager
Marketing Representative
Order Administrator
Owner
Owner/Marketing Assistant
Product Manager
Purchasing Agent
Purchasing Manager
Regional Account Representative
Sales Agent
Sales Associate
Sales Manager
Sales Representative

Owner