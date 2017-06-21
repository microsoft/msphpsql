--TEST--
prepares a statement with a client-side cursor
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$query = "select * from Person.ContactType";
$stmt = $conn->prepare( $query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$stmt->execute();

echo "\n";

while ( $row = $stmt->fetch( PDO::FETCH_ASSOC ) ){
   print "$row[Name]\n";
}
echo "\n..\n";

$row = $stmt->fetch( PDO::FETCH_BOTH, PDO::FETCH_ORI_FIRST );
print_r($row);

$row = $stmt->fetch( PDO::FETCH_ASSOC, PDO::FETCH_ORI_REL, 1 );
print "$row[Name]\n";

$row = $stmt->fetch( PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT );
print "$row[1]\n";

$row = $stmt->fetch( PDO::FETCH_NUM, PDO::FETCH_ORI_PRIOR );
print "$row[1]..\n";

$row = $stmt->fetch( PDO::FETCH_NUM, PDO::FETCH_ORI_ABS, 0 );
print_r($row);

$row = $stmt->fetch( PDO::FETCH_NUM, PDO::FETCH_ORI_LAST );
print_r($row);

//free the statement and connection
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

..
Array
(
    [ContactTypeID] => 1
    [0] => 1
    [Name] => Accounting Manager
    [1] => Accounting Manager
    [ModifiedDate] => 2008-04-30 00:00:00.000
    [2] => 2008-04-30 00:00:00.000
)
Assistant Sales Agent
Assistant Sales Representative
Assistant Sales Agent..
Array
(
    [0] => 1
    [1] => Accounting Manager
    [2] => 2008-04-30 00:00:00.000
)
Array
(
    [0] => 20
    [1] => Sales Representative
    [2] => 2008-04-30 00:00:00.000
)