--TEST--
specifies the fetch mode before fetching
--SKIPIF--

--FILE--
<?php
   require('connect.inc');
   $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

   $stmt1 = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   while ( $row = $stmt1->fetch()) { 
      print($row['Name'] . "\n"); 
   }
   print( "\n---------- PDO::FETCH_ASSOC -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $stmt->setFetchMode(PDO::FETCH_ASSOC);
   $result = $stmt->fetch();
   print_r( $result );

   print( "\n---------- PDO::FETCH_NUM -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $stmt->setFetchMode(PDO::FETCH_NUM);
   $result = $stmt->fetch();
   print_r ($result );

   print( "\n---------- PDO::FETCH_BOTH -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $stmt->setFetchMode(PDO::FETCH_BOTH);
   $result = $stmt->fetch();
   print_r( $result );

   print( "\n---------- PDO::FETCH_LAZY -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $stmt->setFetchMode(PDO::FETCH_LAZY);
   $result = $stmt->fetch();
   print_r( $result );

   print( "\n---------- PDO::FETCH_OBJ -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $stmt->setFetchMode(PDO::FETCH_OBJ);
   $result = $stmt->fetch();
   print $result->Name;
   print( "\n \n" );
   
   //free the statements and connection
   $stmt1 = null;
   $stmt = null;
   $conn = null;
?>
--EXPECT--
Accounting Manager
Assistant Sales Agent
Assistant Sales Representative
Coordinator Foreign Markets

---------- PDO::FETCH_ASSOC -------------
Array
(
    [ContactTypeID] => 1
    [Name] => Accounting Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
)

---------- PDO::FETCH_NUM -------------
Array
(
    [0] => 1
    [1] => Accounting Manager
    [2] => 2008-04-30 00:00:00.000
)

---------- PDO::FETCH_BOTH -------------
Array
(
    [ContactTypeID] => 1
    [0] => 1
    [Name] => Accounting Manager
    [1] => Accounting Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
    [2] => 2008-04-30 00:00:00.000
)

---------- PDO::FETCH_LAZY -------------
PDORow Object
(
    [queryString] => select * from Person.ContactType where ContactTypeID < 5 
    [ContactTypeID] => 1
    [Name] => Accounting Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
)

---------- PDO::FETCH_OBJ -------------
Accounting Manager