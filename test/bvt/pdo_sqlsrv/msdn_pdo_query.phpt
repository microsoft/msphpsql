--TEST--
default query; query for a column; query with a new class; query into an existing class
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");
$conn->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );
$conn->setAttribute( PDO::SQLSRV_ATTR_QUERY_TIMEOUT, 1 );

$query = 'select * from Person.ContactType';

// simple query
$stmt = $conn->query( $query );
while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print_r( $row['Name'] ."\n" );
}

echo "\n........ query for a column ............\n";

// query for one column
$stmt = $conn->query( $query, PDO::FETCH_COLUMN, 1 );
while ( $row = $stmt->fetch() ){
   echo "$row\n";
}

echo "\n........ query with a new class ............\n";
$query = 'select * from HumanResources.Department order by GroupName';
// query with a class
class cc {
   function __construct( $arg ) {
      echo "$arg";
   }

   function __toString() {
      return $this->DepartmentID . "; " . $this->Name . "; " . $this->GroupName;
   }
}

$stmt = $conn->query( $query, PDO::FETCH_CLASS, 'cc', array( "arg1 " ));

while ( $row = $stmt->fetch() ){
   echo "$row\n";
}

echo "\n........ query into an existing class ............\n";
$c_obj = new cc( '' );
$stmt = $conn->query( $query, PDO::FETCH_INTO, $c_obj );
while ( $stmt->fetch() ){
   echo "$c_obj\n";
}

$stmt = null;
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

........ query for a column ............
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

........ query with a new class ............
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

........ query into an existing class ............
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