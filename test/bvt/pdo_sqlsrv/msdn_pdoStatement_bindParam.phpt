--TEST--
after a variable is bound, changing the value changes the value passed in the query
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$contact = "Sales Agent";
$stmt = $conn->prepare("select * from Person.ContactType where name = ?");
$stmt->bindParam(1, $contact);
$contact = "Owner";
$stmt->execute();

while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print "Result: "."$row[Name]\n\n";
}

$stmt = null;
$contact = "Sales Agent";
$stmt = $conn->prepare("select * from Person.ContactType where name = :contact");
$stmt->bindParam(':contact', $contact);
$contact = "Owner";
$stmt->execute();

while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print "Result: "."$row[Name]\n\n";
}

//free the statement and connection
$stmt = null;
$conn = null;
?>
--EXPECT--
Result: Owner

Result: Owner