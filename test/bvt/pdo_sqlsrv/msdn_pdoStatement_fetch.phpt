--TEST--
fetch with all fetch styles
--SKIPIF--

--FILE--
<?php
   require('connect.inc');
   $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

   print( "\n---------- PDO::FETCH_CLASS -------------\n" );
   $stmt = $conn->query( "select * from HumanResources.Department order by GroupName" );

   class cc {
      function __construct( $arg ) {
         echo "$arg";
      }

      function __toString() {
         return $this->DepartmentID . "; " . $this->Name . "; " . $this->GroupName;
      }
   }

   $stmt->setFetchMode(PDO::FETCH_CLASS, 'cc', array( "arg1 " ));
   while ( $row = $stmt->fetch(PDO::FETCH_CLASS)) { 
      print($row . "\n"); 
   }

   print( "\n---------- PDO::FETCH_INTO -------------\n" );
   $stmt = $conn->query( "select * from HumanResources.Department order by GroupName" );
   $c_obj = new cc( '' );

   $stmt->setFetchMode(PDO::FETCH_INTO, $c_obj);
   while ( $row = $stmt->fetch(PDO::FETCH_INTO)) { 
      echo "$c_obj\n";
   }

   print( "\n---------- PDO::FETCH_ASSOC -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetch( PDO::FETCH_ASSOC );
   print_r( $result );

   print( "\n---------- PDO::FETCH_NUM -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetch( PDO::FETCH_NUM );
   print_r ($result );

   print( "\n---------- PDO::FETCH_BOTH -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetch( PDO::FETCH_BOTH );
   print_r( $result );

   print( "\n---------- PDO::FETCH_LAZY -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetch( PDO::FETCH_LAZY );
   print_r( $result );

   print( "\n---------- PDO::FETCH_OBJ -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetch( PDO::FETCH_OBJ );
   print $result->Name;
   print( "\n \n" );

   print( "\n---------- PDO::FETCH_BOUND -------------\n" );
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $stmt->bindColumn('Name', $name);
   $result = $stmt->fetch( PDO::FETCH_BOUND );
   print $name;
   print( "\n \n" );
   
   //free the statement and connection 
   $stmt=null;
   $conn=null;
?>
--EXPECT--
---------- PDO::FETCH_CLASS -------------
arg1 9; Human Resources; Executive General and Administration
arg1 10; Finance; Executive General and Administration
arg1 11; Information Services; Executive General and Administration
arg1 14; Facilities and Maintenance; Executive General and Administration
arg1 16; Executive; Executive General and Administration
arg1 15; Shipping and Receiving; Inventory Management
arg1 5; Purchasing; Inventory Management
arg1 7; Production; Manufacturing
arg1 8; Production Control; Manufacturing
arg1 12; Document Control; Quality Assurance
arg1 13; Quality Assurance; Quality Assurance
arg1 6; Research and Development; Research and Development
arg1 1; Engineering; Research and Development
arg1 2; Tool Design; Research and Development
arg1 3; Sales; Sales and Marketing
arg1 4; Marketing; Sales and Marketing

---------- PDO::FETCH_INTO -------------
9; Human Resources; Executive General and Administration
10; Finance; Executive General and Administration
11; Information Services; Executive General and Administration
14; Facilities and Maintenance; Executive General and Administration
16; Executive; Executive General and Administration
15; Shipping and Receiving; Inventory Management
5; Purchasing; Inventory Management
7; Production; Manufacturing
8; Production Control; Manufacturing
12; Document Control; Quality Assurance
13; Quality Assurance; Quality Assurance
6; Research and Development; Research and Development
1; Engineering; Research and Development
2; Tool Design; Research and Development
3; Sales; Sales and Marketing
4; Marketing; Sales and Marketing

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
 

---------- PDO::FETCH_BOUND -------------
Accounting Manager