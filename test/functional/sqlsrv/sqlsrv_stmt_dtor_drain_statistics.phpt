--TEST--
Verify stmt dtor drains unconsumed result sets from SET STATISTICS (sqlsrv)
--DESCRIPTION--
Exercises the drain path by combining SET STATISTICS PROFILE ON with
an INSERT via sqlsrv_prepare/sqlsrv_execute. STATISTICS PROFILE generates
extra result sets that remain unconsumed when the statement is freed.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$tableName = 'sqlsrv_dtor_drain_stats_test';

$conn = connect();

dropTable($conn, $tableName);
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (id INT IDENTITY(1,1), val VARCHAR(50))");
sqlsrv_free_stmt($stmt);

// SET STATISTICS PROFILE ON generates execution plan result sets
$stmt = sqlsrv_prepare($conn, "SET STATISTICS PROFILE ON; SET NOCOUNT OFF; INSERT INTO $tableName (val) VALUES ('x'), ('y'), ('z')");
sqlsrv_execute($stmt);
// Free without consuming statistics result sets
sqlsrv_free_stmt($stmt);

// Verify with separate connection to avoid STATISTICS PROFILE affecting the SELECT
$conn2 = connect();
$stmt = sqlsrv_query($conn2, "SELECT COUNT(*) AS cnt FROM $tableName");
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Stats test: " . $row['cnt'] . " rows\n";
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn2);

// Cleanup
$stmt = sqlsrv_query($conn, "SET STATISTICS PROFILE OFF");
sqlsrv_free_stmt($stmt);
dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Done.\n";
?>
--EXPECT--
Stats test: 3 rows
Done.
