--TEST--
moves the cursor to the next result set and fetches results
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$query1 = "select AddressID from Person.Address where City = 'Bothell'";
$query2 = "select Name from Person.ContactType";

$stmt = $conn->query( $query1 . $query2);
$rowset1 = $stmt->fetchAll();
$stmt->nextRowset();
$rowset2 = $stmt->fetchAll();
var_dump( $rowset1 );
var_dump( $rowset2 );
   
// free the statement and connection
$stmt = null; 
$conn = null;  
?>
--EXPECT--
array(26) {
  [0]=>
  array(2) {
    ["AddressID"]=>
    string(1) "5"
    [0]=>
    string(1) "5"
  }
  [1]=>
  array(2) {
    ["AddressID"]=>
    string(2) "11"
    [0]=>
    string(2) "11"
  }
  [2]=>
  array(2) {
    ["AddressID"]=>
    string(1) "6"
    [0]=>
    string(1) "6"
  }
  [3]=>
  array(2) {
    ["AddressID"]=>
    string(2) "18"
    [0]=>
    string(2) "18"
  }
  [4]=>
  array(2) {
    ["AddressID"]=>
    string(2) "40"
    [0]=>
    string(2) "40"
  }
  [5]=>
  array(2) {
    ["AddressID"]=>
    string(1) "1"
    [0]=>
    string(1) "1"
  }
  [6]=>
  array(2) {
    ["AddressID"]=>
    string(2) "10"
    [0]=>
    string(2) "10"
  }
  [7]=>
  array(2) {
    ["AddressID"]=>
    string(3) "868"
    [0]=>
    string(3) "868"
  }
  [8]=>
  array(2) {
    ["AddressID"]=>
    string(2) "19"
    [0]=>
    string(2) "19"
  }
  [9]=>
  array(2) {
    ["AddressID"]=>
    string(2) "16"
    [0]=>
    string(2) "16"
  }
  [10]=>
  array(2) {
    ["AddressID"]=>
    string(2) "15"
    [0]=>
    string(2) "15"
  }
  [11]=>
  array(2) {
    ["AddressID"]=>
    string(2) "12"
    [0]=>
    string(2) "12"
  }
  [12]=>
  array(2) {
    ["AddressID"]=>
    string(5) "18249"
    [0]=>
    string(5) "18249"
  }
  [13]=>
  array(2) {
    ["AddressID"]=>
    string(1) "7"
    [0]=>
    string(1) "7"
  }
  [14]=>
  array(2) {
    ["AddressID"]=>
    string(2) "21"
    [0]=>
    string(2) "21"
  }
  [15]=>
  array(2) {
    ["AddressID"]=>
    string(1) "8"
    [0]=>
    string(1) "8"
  }
  [16]=>
  array(2) {
    ["AddressID"]=>
    string(2) "17"
    [0]=>
    string(2) "17"
  }
  [17]=>
  array(2) {
    ["AddressID"]=>
    string(2) "20"
    [0]=>
    string(2) "20"
  }
  [18]=>
  array(2) {
    ["AddressID"]=>
    string(5) "26486"
    [0]=>
    string(5) "26486"
  }
  [19]=>
  array(2) {
    ["AddressID"]=>
    string(1) "3"
    [0]=>
    string(1) "3"
  }
  [20]=>
  array(2) {
    ["AddressID"]=>
    string(2) "14"
    [0]=>
    string(2) "14"
  }
  [21]=>
  array(2) {
    ["AddressID"]=>
    string(1) "9"
    [0]=>
    string(1) "9"
  }
  [22]=>
  array(2) {
    ["AddressID"]=>
    string(2) "13"
    [0]=>
    string(2) "13"
  }
  [23]=>
  array(2) {
    ["AddressID"]=>
    string(1) "4"
    [0]=>
    string(1) "4"
  }
  [24]=>
  array(2) {
    ["AddressID"]=>
    string(1) "2"
    [0]=>
    string(1) "2"
  }
  [25]=>
  array(2) {
    ["AddressID"]=>
    string(3) "834"
    [0]=>
    string(3) "834"
  }
}
array(20) {
  [0]=>
  array(2) {
    ["Name"]=>
    string(18) "Accounting Manager"
    [0]=>
    string(18) "Accounting Manager"
  }
  [1]=>
  array(2) {
    ["Name"]=>
    string(21) "Assistant Sales Agent"
    [0]=>
    string(21) "Assistant Sales Agent"
  }
  [2]=>
  array(2) {
    ["Name"]=>
    string(30) "Assistant Sales Representative"
    [0]=>
    string(30) "Assistant Sales Representative"
  }
  [3]=>
  array(2) {
    ["Name"]=>
    string(27) "Coordinator Foreign Markets"
    [0]=>
    string(27) "Coordinator Foreign Markets"
  }
  [4]=>
  array(2) {
    ["Name"]=>
    string(20) "Export Administrator"
    [0]=>
    string(20) "Export Administrator"
  }
  [5]=>
  array(2) {
    ["Name"]=>
    string(31) "International Marketing Manager"
    [0]=>
    string(31) "International Marketing Manager"
  }
  [6]=>
  array(2) {
    ["Name"]=>
    string(19) "Marketing Assistant"
    [0]=>
    string(19) "Marketing Assistant"
  }
  [7]=>
  array(2) {
    ["Name"]=>
    string(17) "Marketing Manager"
    [0]=>
    string(17) "Marketing Manager"
  }
  [8]=>
  array(2) {
    ["Name"]=>
    string(24) "Marketing Representative"
    [0]=>
    string(24) "Marketing Representative"
  }
  [9]=>
  array(2) {
    ["Name"]=>
    string(19) "Order Administrator"
    [0]=>
    string(19) "Order Administrator"
  }
  [10]=>
  array(2) {
    ["Name"]=>
    string(5) "Owner"
    [0]=>
    string(5) "Owner"
  }
  [11]=>
  array(2) {
    ["Name"]=>
    string(25) "Owner/Marketing Assistant"
    [0]=>
    string(25) "Owner/Marketing Assistant"
  }
  [12]=>
  array(2) {
    ["Name"]=>
    string(15) "Product Manager"
    [0]=>
    string(15) "Product Manager"
  }
  [13]=>
  array(2) {
    ["Name"]=>
    string(16) "Purchasing Agent"
    [0]=>
    string(16) "Purchasing Agent"
  }
  [14]=>
  array(2) {
    ["Name"]=>
    string(18) "Purchasing Manager"
    [0]=>
    string(18) "Purchasing Manager"
  }
  [15]=>
  array(2) {
    ["Name"]=>
    string(31) "Regional Account Representative"
    [0]=>
    string(31) "Regional Account Representative"
  }
  [16]=>
  array(2) {
    ["Name"]=>
    string(11) "Sales Agent"
    [0]=>
    string(11) "Sales Agent"
  }
  [17]=>
  array(2) {
    ["Name"]=>
    string(15) "Sales Associate"
    [0]=>
    string(15) "Sales Associate"
  }
  [18]=>
  array(2) {
    ["Name"]=>
    string(13) "Sales Manager"
    [0]=>
    string(13) "Sales Manager"
  }
  [19]=>
  array(2) {
    ["Name"]=>
    string(20) "Sales Representative"
    [0]=>
    string(20) "Sales Representative"
  }
}