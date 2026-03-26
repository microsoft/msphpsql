--TEST--
Prepared statement insert with additional result sets should not silently lose data
--DESCRIPTION--
When SET STATISTICS PROFILE ON or triggers produce extra result sets, a prepared
statement insert should still persist data. Previously, unconsumed result sets left
pending on the ODBC statement handle could be cancelled during statement destruction,
causing an implicit rollback of the insert.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$tableName = 'pdo_insert_extra_results_test';

try {
    $conn = connect();

    // Create test table
    $conn->exec("IF OBJECT_ID('$tableName', 'U') IS NOT NULL DROP TABLE $tableName");
    $conn->exec("CREATE TABLE $tableName (id INT IDENTITY(1,1) PRIMARY KEY, val VARCHAR(50))");

    // Test 1: Insert with SET STATISTICS PROFILE ON using a prepared statement
    // SET STATISTICS PROFILE ON causes SQL Server to return additional result sets
    // with query execution plan data after each statement's normal results.
    $stmt = $conn->prepare("SET STATISTICS PROFILE ON; INSERT INTO $tableName (val) VALUES ('row1'), ('row2'), ('row3')");
    $stmt->execute();
    $stmt = null; // destroy the statement - this must not cancel the insert

    $stmt = $conn->query("SELECT COUNT(*) FROM $tableName");
    $count = $stmt->fetchColumn();
    if ($count != 3) {
        echo "FAIL: Expected 3 rows after prepared insert with STATISTICS PROFILE, got $count\n";
    } else {
        echo "Test 1 passed: $count rows inserted via prepared statement with STATISTICS PROFILE.\n";
    }
    $stmt = null;

    // Test 2: Insert with SET NOCOUNT OFF (which returns row count messages as result sets)
    // combined with STATISTICS PROFILE
    $conn->exec("DELETE FROM $tableName");
    $stmt = $conn->prepare("SET STATISTICS PROFILE ON; SET NOCOUNT OFF; INSERT INTO $tableName (val) VALUES ('a'), ('b'), ('c'), ('d'), ('e')");
    $stmt->execute();
    $stmt = null; // destroy statement before consuming additional result sets

    $stmt = $conn->query("SELECT COUNT(*) FROM $tableName");
    $count = $stmt->fetchColumn();
    if ($count != 5) {
        echo "FAIL: Expected 5 rows after prepared insert with STATISTICS PROFILE + NOCOUNT OFF, got $count\n";
    } else {
        echo "Test 2 passed: $count rows inserted via prepared statement with STATISTICS PROFILE + NOCOUNT OFF.\n";
    }
    $stmt = null;

    // Test 3: Insert via PDO::exec (this already worked before the fix)
    $conn->exec("DELETE FROM $tableName");
    $conn->exec("SET STATISTICS PROFILE ON; SET NOCOUNT OFF; INSERT INTO $tableName (val) VALUES ('x'), ('y')");

    // Need a new connection or turn off stats to do a clean SELECT
    $conn2 = connect();
    $stmt = $conn2->query("SELECT COUNT(*) FROM $tableName");
    $count = $stmt->fetchColumn();
    if ($count != 2) {
        echo "FAIL: Expected 2 rows after exec insert, got $count\n";
    } else {
        echo "Test 3 passed: $count rows inserted via exec.\n";
    }
    $stmt = null;
    $conn2 = null;

    // Test 4: Insert with trigger generating extra result sets
    $conn->exec("SET STATISTICS PROFILE OFF");
    $conn->exec("DELETE FROM $tableName");

    // Create a trigger that produces extra result sets
    $conn->exec("IF OBJECT_ID('trg_insert_$tableName', 'TR') IS NOT NULL DROP TRIGGER trg_insert_$tableName");
    $conn->exec("
        CREATE TRIGGER trg_insert_$tableName ON $tableName
        AFTER INSERT AS
        BEGIN
            SELECT COUNT(*) AS trigger_count FROM $tableName
        END
    ");

    $stmt = $conn->prepare("INSERT INTO $tableName (val) VALUES ('t1'), ('t2'), ('t3')");
    $stmt->execute();
    $stmt = null; // destroy statement with unconsumed trigger result set

    $stmt = $conn->query("SELECT COUNT(*) FROM $tableName");
    $count = $stmt->fetchColumn();
    if ($count != 3) {
        echo "FAIL: Expected 3 rows after insert with trigger, got $count\n";
    } else {
        echo "Test 4 passed: $count rows inserted via prepared statement with trigger.\n";
    }

    // Cleanup
    $conn->exec("DROP TRIGGER trg_insert_$tableName");
    $conn->exec("DROP TABLE $tableName");
    $conn = null;

    echo "Done.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
--EXPECT--
Test 1 passed: 3 rows inserted via prepared statement with STATISTICS PROFILE.
Test 2 passed: 5 rows inserted via prepared statement with STATISTICS PROFILE + NOCOUNT OFF.
Test 3 passed: 2 rows inserted via exec.
Test 4 passed: 3 rows inserted via prepared statement with trigger.
Done.
