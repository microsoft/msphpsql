--TEST--
Verify stmt dtor drains unconsumed result sets to prevent silent data loss (sqlsrv)
--DESCRIPTION--
When triggers, SET STATISTICS PROFILE ON, or multi-result batches produce extra
result sets, the statement destructor must drain them before freeing the ODBC
handle. Otherwise, with MARS enabled, freeing the handle cancels the batch and
silently rolls back uncommitted implicit transactions.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$tableName = getTempTableName('sqlsrv_drain_results', false);
$triggerName = 'trg_' . $tableName;

$conn = connect();
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Create test table
dropTable($conn, $tableName);
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (id INT IDENTITY(1,1) PRIMARY KEY, val VARCHAR(50))");
if ($stmt === false) die(print_r(sqlsrv_errors(), true));
sqlsrv_free_stmt($stmt);

// Test 1: sqlsrv_prepare + execute with STATISTICS PROFILE
$stmt = sqlsrv_prepare($conn, "SET STATISTICS PROFILE ON; INSERT INTO $tableName (val) VALUES ('row1'), ('row2'), ('row3')");
if ($stmt === false) die(print_r(sqlsrv_errors(), true));
if (!sqlsrv_execute($stmt)) die(print_r(sqlsrv_errors(), true));
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM $tableName");
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Test 1 (STATISTICS PROFILE): " . ($row['cnt'] == 3 ? "PASS" : "FAIL (got {$row['cnt']})") . "\n";
sqlsrv_free_stmt($stmt);

// Test 2: sqlsrv_query with STATISTICS PROFILE + NOCOUNT OFF
$stmt = sqlsrv_query($conn, "DELETE FROM $tableName");
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SET STATISTICS PROFILE ON; SET NOCOUNT OFF; INSERT INTO $tableName (val) VALUES ('a'), ('b'), ('c'), ('d'), ('e')");
if ($stmt === false) die(print_r(sqlsrv_errors(), true));
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM $tableName");
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Test 2 (STATISTICS + NOCOUNT): " . ($row['cnt'] == 5 ? "PASS" : "FAIL (got {$row['cnt']})") . "\n";
sqlsrv_free_stmt($stmt);

// Test 3: Trigger producing extra result sets
$stmt = sqlsrv_query($conn, "SET STATISTICS PROFILE OFF");
sqlsrv_free_stmt($stmt);
$stmt = sqlsrv_query($conn, "DELETE FROM $tableName");
sqlsrv_free_stmt($stmt);

sqlsrv_query($conn, "IF OBJECT_ID('$triggerName', 'TR') IS NOT NULL DROP TRIGGER $triggerName");
$stmt = sqlsrv_query($conn, "
    CREATE TRIGGER $triggerName ON $tableName
    AFTER INSERT AS
    BEGIN
        SELECT COUNT(*) AS trigger_count FROM $tableName
    END
");
if ($stmt === false) die(print_r(sqlsrv_errors(), true));
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (val) VALUES ('t1'), ('t2'), ('t3')");
if (!sqlsrv_execute($stmt)) die(print_r(sqlsrv_errors(), true));
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM $tableName");
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Test 3 (trigger): " . ($row['cnt'] == 3 ? "PASS" : "FAIL (got {$row['cnt']})") . "\n";
sqlsrv_free_stmt($stmt);

// Test 4: Multi-result SELECT batch with partial consumption
$stmt = sqlsrv_query($conn, "SELECT 1 AS a; SELECT 2 AS b; SELECT 3 AS c");
sqlsrv_fetch($stmt);
$first = sqlsrv_get_field($stmt, 0);
echo "Test 4 (multi-result first): " . ($first == 1 ? "PASS" : "FAIL") . "\n";
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT 42 AS answer");
sqlsrv_fetch($stmt);
$answer = sqlsrv_get_field($stmt, 0);
echo "Test 4 (after drain): " . ($answer == 42 ? "PASS" : "FAIL") . "\n";
sqlsrv_free_stmt($stmt);

// Cleanup — runs regardless of test outcome
sqlsrv_query($conn, "IF OBJECT_ID('$triggerName', 'TR') IS NOT NULL DROP TRIGGER $triggerName");
dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Done.\n";
?>
--EXPECT--
Test 1 (STATISTICS PROFILE): PASS
Test 2 (STATISTICS + NOCOUNT): PASS
Test 3 (trigger): PASS
Test 4 (multi-result first): PASS
Test 4 (after drain): PASS
Done.
