--TEST--
connect to a server, specifying the database later
--SKIPIF--

--FILE--
<?php
   require('connect.inc');
   $c = new PDO( "sqlsrv:Server=$server", "$uid", "$pwd");

   $c->exec( "USE $databaseName");
   $query = 'SELECT * FROM Person.ContactType';
   $stmt = $c->query( $query );
   while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
      print_r( $row );
   }
   $stmt=null;
   $c = null;
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