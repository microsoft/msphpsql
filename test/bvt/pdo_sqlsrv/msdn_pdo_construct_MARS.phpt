--TEST--
connect to a server, setting MARS to false
--SKIPIF--

--FILE--
<?php
require('connect.inc');
// Connect to the local server using Windows Authentication and AdventureWorks database

try {
   $conn = new PDO( "sqlsrv:Server=$server ; Database = $databaseName ; MultipleActiveResultSets=false", "$uid", "$pwd");
   $conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION ); 
}

catch( PDOException $e ) {
   die( "Error connecting to SQL Server" ); 
}

$query = 'SELECT * FROM Person.ContactType';
$stmt = $conn->query( $query );
while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print_r( $row );
}
   
$stmt=null;
$conn = null; 
?>
--EXPECT--
Array
(
    [ContactTypeID] => 1
    [Name] => Accounting Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 2
    [Name] => Assistant Sales Agent
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 3
    [Name] => Assistant Sales Representative
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 4
    [Name] => Coordinator Foreign Markets
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 5
    [Name] => Export Administrator
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 6
    [Name] => International Marketing Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 7
    [Name] => Marketing Assistant
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 8
    [Name] => Marketing Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 9
    [Name] => Marketing Representative
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 10
    [Name] => Order Administrator
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 11
    [Name] => Owner
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 12
    [Name] => Owner/Marketing Assistant
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 13
    [Name] => Product Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 14
    [Name] => Purchasing Agent
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 15
    [Name] => Purchasing Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 16
    [Name] => Regional Account Representative
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 17
    [Name] => Sales Agent
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 18
    [Name] => Sales Associate
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 19
    [Name] => Sales Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
)
Array
(
    [ContactTypeID] => 20
    [Name] => Sales Representative
    [ModifiedDate] => 2008-04-30 00:00:00.000
)