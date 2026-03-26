--TEST--
Verify stmt dtor drains unconsumed result sets from triggers (sqlsrv)
--DESCRIPTION--
Exercises the drain code in sqlsrv_stmt_dtor that consumes pending
result sets when a statement is freed with unconsumed results.
A trigger on INSERT produces an extra result set that must be drained
in the destructor to prevent implicit rollback.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$tableName = 'sqlsrv_dtor_drain_trigger_test';

$conn = connect();

// Setup: create table and trigger that produces an extra result set
dropTable($conn, $tableName);
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (id INT IDENTITY(1,1), val VARCHAR(50))");
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "
    CREATE TRIGGER trg_$tableName ON $tableName
    AFTER INSERT AS
    BEGIN
        SELECT COUNT(*) AS cnt FROM $tableName
    END
");
sqlsrv_free_stmt($stmt);

// Execute a prepared INSERT — the trigger generates an unconsumed result set
$stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (val) VALUES ('a'), ('b')");
sqlsrv_execute($stmt);
// Free the statement without consuming the trigger result set.
// This forces the drain path in sqlsrv_stmt_dtor.
sqlsrv_free_stmt($stmt);

// Verify the insert was committed (not rolled back)
$stmt = sqlsrv_query($conn, "SELECT COUNT(*) AS cnt FROM $tableName");
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Trigger test: " . $row['cnt'] . " rows\n";
sqlsrv_free_stmt($stmt);

// Cleanup
$stmt = sqlsrv_query($conn, "DROP TRIGGER trg_$tableName");
sqlsrv_free_stmt($stmt);
dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Done.\n";
?>
--EXPECT--
Trigger test: 2 rows
Done.
