--TEST--
prepares a statement with parameter markers and forward-only (server-side) cursor
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('connect.inc');
$conn = new PDO("sqlsrv:Server=$server; Database = $databaseName", $uid, $pwd);

$tableName = "pdoPrepare";
dropTable($conn, $tableName);

$conn->exec("CREATE TABLE $tableName(col1 VARCHAR(100), col2 VARCHAR(100))");

$col1 = 'a';
$col2 = 'b';

$query = "INSERT INTO $tableName(col1, col2) VALUES(?, ?)";
$stmt = $conn->prepare( $query, array( PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY, PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 1  ) );
$stmt->execute( array( $col1, $col2 ) );
print $stmt->rowCount();
echo " row affected\n";

$query = "INSERT INTO $tableName(col1, col2) VALUES(:col1, :col2)";
$stmt = $conn->prepare( $query, array( PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY, PDO::SQLSRV_ATTR_QUERY_TIMEOUT => 1  ) );
$stmt->execute( array( ':col1' => $col1, ':col2' => $col2 ) );
print $stmt->rowCount();
echo " row affected\n";

// revert the inserts
$conn->exec("DELETE FROM $tableName WHERE col1 = 'a' AND col2 = 'b'");
dropTable($conn, $tableName, false);

unset($stmt);
unset($conn);
?>
--EXPECT--
1 row affected
1 row affected