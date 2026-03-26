--TEST--
Verify stmt dtor drains unconsumed result sets from SET STATISTICS
--DESCRIPTION--
Exercises the drain path by combining SET STATISTICS PROFILE ON with
an INSERT. STATISTICS PROFILE generates extra result sets that remain
unconsumed when the statement is freed.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$tableName = 'pdo_dtor_drain_stats_test';

$conn = connect();

$conn->exec("IF OBJECT_ID('$tableName', 'U') IS NOT NULL DROP TABLE $tableName");
$conn->exec("CREATE TABLE $tableName (id INT IDENTITY(1,1), val VARCHAR(50))");

// SET STATISTICS PROFILE ON generates execution plan result sets after each statement
$stmt = $conn->prepare("SET STATISTICS PROFILE ON; SET NOCOUNT OFF; INSERT INTO $tableName (val) VALUES ('x'), ('y'), ('z')");
$stmt->execute();
// Don't fetch or call nextRowset — destroy immediately
unset($stmt);

// Use separate connection to verify (avoids STATISTICS PROFILE affecting SELECT)
$conn2 = connect();
$row = $conn2->query("SELECT COUNT(*) AS cnt FROM $tableName")->fetch(PDO::FETCH_ASSOC);
echo "Stats test: " . $row['cnt'] . " rows\n";

// Cleanup
$conn->exec("SET STATISTICS PROFILE OFF");
$conn->exec("DROP TABLE $tableName");
unset($conn2);
unset($conn);

echo "Done.\n";
?>
--EXPECT--
Stats test: 3 rows
Done.
