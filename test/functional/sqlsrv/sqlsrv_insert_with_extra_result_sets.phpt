--TEST--
Prepared statement insert with additional result sets should not silently lose data
--DESCRIPTION--
When SET STATISTICS PROFILE ON or triggers produce extra result sets, executing a
prepared statement insert should still persist data. Previously, unconsumed result
sets left pending on the ODBC statement handle could be cancelled during statement
destruction, causing an implicit rollback of the insert.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$tableName = 'sqlsrv_insert_extra_results_test';

$conn = connect();
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

// Create test table
dropTable($conn, $tableName);
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (id INT IDENTITY(1,1) PRIMARY KEY, val VARCHAR(50))");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_free_stmt($stmt);

// Test 1: sqlsrv_prepare + execute with STATISTICS PROFILE generating extra result sets
$stmt = sqlsrv_prepare($conn, "SET STATISTICS PROFILE ON; INSERT INTO $tableName (val) VALUES ('row1'), ('row2'), ('row3')");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
if (!sqlsrv_execute($stmt)) {
    die(print_r(sqlsrv_errors(), true));
}
// Free the statement without consuming all result sets
sqlsrv_free_stmt($stmt);

// Verify data persisted
$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM $tableName");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if ($row['cnt'] != 3) {
    echo "FAIL: Expected 3 rows after prepared insert with STATISTICS PROFILE, got " . $row['cnt'] . "\n";
} else {
    echo "Test 1 passed: " . $row['cnt'] . " rows inserted via sqlsrv_prepare with STATISTICS PROFILE.\n";
}
sqlsrv_free_stmt($stmt);

// Test 2: sqlsrv_query with STATISTICS PROFILE + NOCOUNT OFF
$stmt = sqlsrv_query($conn, "DELETE FROM $tableName");
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SET STATISTICS PROFILE ON; SET NOCOUNT OFF; INSERT INTO $tableName (val) VALUES ('a'), ('b'), ('c'), ('d'), ('e')");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
// Free without consuming extra result sets
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM $tableName");
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if ($row['cnt'] != 5) {
    echo "FAIL: Expected 5 rows after query with STATISTICS PROFILE + NOCOUNT OFF, got " . $row['cnt'] . "\n";
} else {
    echo "Test 2 passed: " . $row['cnt'] . " rows inserted via sqlsrv_query with STATISTICS PROFILE + NOCOUNT OFF.\n";
}
sqlsrv_free_stmt($stmt);

// Test 3: Insert with trigger producing extra result sets
$stmt = sqlsrv_query($conn, "SET STATISTICS PROFILE OFF");
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "DELETE FROM $tableName");
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "
    IF OBJECT_ID('trg_insert_$tableName', 'TR') IS NOT NULL DROP TRIGGER trg_insert_$tableName
");
if ($stmt !== false) sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "
    CREATE TRIGGER trg_insert_$tableName ON $tableName
    AFTER INSERT AS
    BEGIN
        SELECT COUNT(*) AS trigger_count FROM $tableName
    END
");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (val) VALUES ('t1'), ('t2'), ('t3')");
if (!sqlsrv_execute($stmt)) {
    die(print_r(sqlsrv_errors(), true));
}
// Free without consuming trigger result set
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM $tableName");
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
if ($row['cnt'] != 3) {
    echo "FAIL: Expected 3 rows after insert with trigger, got " . $row['cnt'] . "\n";
} else {
    echo "Test 3 passed: " . $row['cnt'] . " rows inserted via prepared statement with trigger.\n";
}
sqlsrv_free_stmt($stmt);

// Cleanup
$stmt = sqlsrv_query($conn, "DROP TRIGGER trg_insert_$tableName");
if ($stmt !== false) sqlsrv_free_stmt($stmt);
dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Done.\n";
?>
--EXPECT--
Test 1 passed: 3 rows inserted via sqlsrv_prepare with STATISTICS PROFILE.
Test 2 passed: 5 rows inserted via sqlsrv_query with STATISTICS PROFILE + NOCOUNT OFF.
Test 3 passed: 3 rows inserted via prepared statement with trigger.
Done.
