--TEST--
Verify stmt dtor drains unconsumed result sets to prevent silent data loss
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

$tableName = GetTempTableName('pdo_drain_results', false);
$triggerName = 'trg_' . $tableName;

try {
    $conn = connect();

    // Create test table
    $conn->exec("IF OBJECT_ID('$tableName', 'U') IS NOT NULL DROP TABLE $tableName");
    $conn->exec("CREATE TABLE $tableName (id INT IDENTITY(1,1) PRIMARY KEY, val VARCHAR(50))");

    // Test 1: Prepared statement with SET STATISTICS PROFILE ON
    $stmt = $conn->prepare("SET STATISTICS PROFILE ON; INSERT INTO $tableName (val) VALUES ('row1'), ('row2'), ('row3')");
    $stmt->execute();
    $stmt = null;

    $count = $conn->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
    echo "Test 1 (STATISTICS PROFILE): " . ($count == 3 ? "PASS" : "FAIL (got $count)") . "\n";

    // Test 2: Prepared statement with STATISTICS PROFILE + NOCOUNT OFF
    $conn->exec("DELETE FROM $tableName");
    $stmt = $conn->prepare("SET STATISTICS PROFILE ON; SET NOCOUNT OFF; INSERT INTO $tableName (val) VALUES ('a'), ('b'), ('c'), ('d'), ('e')");
    $stmt->execute();
    $stmt = null;

    $count = $conn->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
    echo "Test 2 (STATISTICS + NOCOUNT): " . ($count == 5 ? "PASS" : "FAIL (got $count)") . "\n";

    // Test 3: PDO::exec baseline (already worked before the fix)
    $conn->exec("DELETE FROM $tableName");
    $conn->exec("SET STATISTICS PROFILE ON; SET NOCOUNT OFF; INSERT INTO $tableName (val) VALUES ('x'), ('y')");
    $conn2 = connect();
    $count = $conn2->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
    $conn2 = null;
    echo "Test 3 (exec baseline): " . ($count == 2 ? "PASS" : "FAIL (got $count)") . "\n";

    // Test 4: Trigger producing extra result sets
    $conn->exec("SET STATISTICS PROFILE OFF");
    $conn->exec("DELETE FROM $tableName");
    $conn->exec("IF OBJECT_ID('$triggerName', 'TR') IS NOT NULL DROP TRIGGER $triggerName");
    $conn->exec("
        CREATE TRIGGER $triggerName ON $tableName
        AFTER INSERT AS
        BEGIN
            SELECT COUNT(*) AS trigger_count FROM $tableName
        END
    ");

    $stmt = $conn->prepare("INSERT INTO $tableName (val) VALUES ('t1'), ('t2'), ('t3')");
    $stmt->execute();
    unset($stmt);

    $count = $conn->query("SELECT COUNT(*) FROM $tableName")->fetchColumn();
    echo "Test 4 (trigger): " . ($count == 3 ? "PASS" : "FAIL (got $count)") . "\n";

    // Test 5: Multi-result SELECT batch with partial consumption
    $stmt = $conn->prepare("SELECT 1 AS a; SELECT 2 AS b; SELECT 3 AS c");
    $stmt->execute();
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Test 5 (multi-result first): " . ($row['a'] == 1 ? "PASS" : "FAIL") . "\n";
    unset($stmt);

    $row = $conn->query("SELECT 42 AS answer")->fetch(PDO::FETCH_ASSOC);
    echo "Test 5 (after drain): " . ($row['answer'] == 42 ? "PASS" : "FAIL") . "\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}

// Cleanup outside try block — runs even on failure
$conn = connect();
$conn->exec("IF OBJECT_ID('$triggerName', 'TR') IS NOT NULL DROP TRIGGER $triggerName");
$conn->exec("IF OBJECT_ID('$tableName', 'U') IS NOT NULL DROP TABLE $tableName");
$conn = null;

echo "Done.\n";
?>
--EXPECT--
Test 1 (STATISTICS PROFILE): PASS
Test 2 (STATISTICS + NOCOUNT): PASS
Test 3 (exec baseline): PASS
Test 4 (trigger): PASS
Test 5 (multi-result first): PASS
Test 5 (after drain): PASS
Done.
