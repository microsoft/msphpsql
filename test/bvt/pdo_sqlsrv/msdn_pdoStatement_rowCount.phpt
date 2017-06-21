--TEST--
returns the number of rows added to a table; returns the number of rows in a result set when you specify a scrollable cursor
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = tempdb", "$uid", "$pwd");
$conn->exec("CREAtE TABLE Table1(col1 VARCHAR(15), col2 VARCHAR(15)) ");
   
$col1 = 'a';
$col2 = 'b';

$query = "insert into Table1(col1, col2) values(?, ?)";
$stmt = $conn->prepare( $query );
$stmt->execute( array( $col1, $col2 ) );
print $stmt->rowCount();
print " rows affects.";

echo "\n\n";

//revert the insert
$conn->exec("delete from Table1 where col1 = 'a' AND col2 = 'b'");

$conn->exec("DROP TABLE Table1 ");

$conn = null;

$conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

$query = "select * from Person.ContactType";
$stmt = $conn->prepare( $query, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
$stmt->execute();
print $stmt->rowCount();
print " rows in result set.";


//free the statement and connection
$stmt = null;
$conn = null;
?>
--EXPECT--
1 rows affects.

20 rows in result set.