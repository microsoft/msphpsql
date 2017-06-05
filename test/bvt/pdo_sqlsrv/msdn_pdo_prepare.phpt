--TEST--
prepares a statement with parameter markers and forward-only (server-side) cursor
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$conn = new PDO( "sqlsrv:server=$server ; Database = tempdb", "$uid", "$pwd");

$conn->exec("CREAtE TABLE Table1(col1 VARCHAR(100), col2 VARCHAR(100))");

$col1 = 'a';
$col2 = 'b';

$query = "insert into Table1(col1, col2) values(?, ?)";
$stmt = $conn->prepare( $query, array( PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY, PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 1  ) );
$stmt->execute( array( $col1, $col2 ) );
print $stmt->rowCount();
echo " row affected\n";

$query = "insert into Table1(col1, col2) values(:col1, :col2)";
$stmt = $conn->prepare( $query, array( PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY, PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 1  ) );
$stmt->execute( array( ':col1' => $col1, ':col2' => $col2 ) );
print $stmt->rowCount();
echo " row affected\n";

// revert the inserts
$conn->exec("delete from Table1 where col1 = 'a' AND col2 = 'b'");

$conn->exec("DROP TABLE Table1 ");
$stmt = null;
$conn = null;
?>
--EXPECT--
1 row affected
1 row affected