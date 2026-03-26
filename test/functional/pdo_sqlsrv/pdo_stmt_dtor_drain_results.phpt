--TEST--
Verify stmt dtor drains unconsumed result sets from triggers
--DESCRIPTION--
Exercises the drain code in pdo_sqlsrv_stmt_dtor that consumes pending
result sets when a statement is destroyed with unconsumed results.
A trigger on INSERT produces an extra result set that must be drained
in the destructor to prevent implicit rollback.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$tableName = 'pdo_dtor_drain_trigger_test';

$conn = connect();

// Setup: create table and trigger that produces an extra result set
$conn->exec("IF OBJECT_ID('$tableName', 'U') IS NOT NULL DROP TABLE $tableName");
$conn->exec("CREATE TABLE $tableName (id INT IDENTITY(1,1), val VARCHAR(50))");
$conn->exec("
    CREATE TRIGGER trg_$tableName ON $tableName
    AFTER INSERT AS
    BEGIN
        SELECT COUNT(*) AS cnt FROM $tableName
    END
");

// Execute a prepared INSERT — the trigger generates an unconsumed result set
$stmt = $conn->prepare("INSERT INTO $tableName (val) VALUES ('a'), ('b')");
$stmt->execute();
// Destroy the statement without consuming the trigger result set.
// This forces the drain path in pdo_sqlsrv_stmt_dtor.
unset($stmt);

// Verify the insert was committed (not rolled back)
$row = $conn->query("SELECT COUNT(*) AS cnt FROM $tableName")->fetch(PDO::FETCH_ASSOC);
echo "Trigger test: " . $row['cnt'] . " rows\n";

// Cleanup
$conn->exec("DROP TRIGGER trg_$tableName");
$conn->exec("DROP TABLE $tableName");
unset($conn);

echo "Done.\n";
?>
--EXPECT--
Trigger test: 2 rows
Done.
