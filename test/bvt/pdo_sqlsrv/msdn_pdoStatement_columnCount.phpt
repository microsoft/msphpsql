--TEST--
returns the number of columns in a result set for 3 queries
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$query = "select * from Person.ContactType";
$stmt = $conn->prepare( $query );
print $stmt->columnCount();   // 0
echo " columns in the result set\n";

echo "\n";
$stmt->execute();
print $stmt->columnCount();
echo " columns in the result set\n";

echo "\n";
$stmt = $conn->query("select * from HumanResources.Department");
print $stmt->columnCount();
echo " columns in the result set\n";

//free the statement and connection
$stmt=null;
$conn=null;
?>
--EXPECT--
0 columns in the result set

3 columns in the result set

4 columns in the result set
