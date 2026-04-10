--TEST--
GitHub issue 1466 - Re-executing prepared statement with multiple result sets
--DESCRIPTION--
Verifies that re-executing a prepared statement returning multiple result sets
does not cause a fatal error or return corrupted data.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect();
    $conn->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $sql = "
        SET NOCOUNT ON;
        DECLARE @id INT = :id;
        -- Result set 1
        SELECT @id AS r1col1, 2 AS r1col2, 3 AS r1col3, N'Test' AS r1col4
        WHERE @id IN (2, 3);
        -- Result set 2
        SELECT @id AS r2col1, N'/test.txt' AS r2col2, N'text/plain' AS r2col3
        WHERE @id IN (2, 3);
    ";

    $stmt = $conn->prepare($sql);

    $idList = [2, 1, 3];
    foreach ($idList as $id) {
        $stmt->bindValue(':id', $id);
        $stmt->execute();

        // Fetch result set 1
        $rows1 = $stmt->fetchAll();
        foreach ($rows1 as $row) {
            if (!array_key_exists('r1col1', $row)) {
                throw new RuntimeException("Result set 1 row missing expected column 'r1col1'.");
            }
            if ($row['r1col1'] != $id) {
                throw new RuntimeException("Result set 1 r1col1 expected $id, got {$row['r1col1']}.");
            }
        }

        // Move to result set 2
        $stmt->nextRowset();
        $rows2 = $stmt->fetchAll();
        foreach ($rows2 as $row) {
            if (!array_key_exists('r2col1', $row)) {
                throw new RuntimeException("Result set 2 row missing expected column 'r2col1'.");
            }
            if ($row['r2col1'] != $id) {
                throw new RuntimeException("Result set 2 r2col1 expected $id, got {$row['r2col1']}.");
            }
        }

        $stmt->closeCursor();

        // Verify row counts based on input
        if ($id == 1) {
            // id=1 is not in (2,3), so both result sets should be empty
            if (count($rows1) !== 0 || count($rows2) !== 0) {
                throw new RuntimeException("Expected empty result sets for id=1.");
            }
        } else {
            // id=2 or id=3 should return one row per result set
            if (count($rows1) !== 1 || count($rows2) !== 1) {
                throw new RuntimeException("Expected one row per result set for id=$id.");
            }
        }
    }
    echo "Test 1 passed: re-execute with closeCursor\n";

    // Test 2: Re-execute without closeCursor (PDO auto-flushes remaining results)
    // This exercises the flush loop in pdo_sqlsrv_stmt_execute that calls
    // core_sqlsrv_next_result before the new execute.
    $stmt2 = $conn->prepare($sql);
    $stmt2->bindValue(':id', 2);
    $stmt2->execute();
    $row = $stmt2->fetch();
    // Do NOT call nextRowset or closeCursor -- just re-execute immediately
    $stmt2->bindValue(':id', 3);
    $stmt2->execute();
    $rows = $stmt2->fetchAll();
    if (count($rows) !== 1 || $rows[0]['r1col1'] != 3) {
        throw new RuntimeException("Test 2 failed: unexpected result after re-execute without closeCursor.");
    }
    $stmt2->closeCursor();
    echo "Test 2 passed: re-execute without closeCursor\n";

    // Test 3: Re-execute when both result sets have the same number of columns
    // but different column names.  Ensures stale column descriptors are refreshed
    // even when the column count hasn't changed.
    $sql3 = "
        SET NOCOUNT ON;
        DECLARE @v INT = :val;
        SELECT @v AS col_a, N'first' AS col_b;
        SELECT @v AS col_x, N'second' AS col_y;
    ";
    $stmt3 = $conn->prepare($sql3);
    for ($i = 1; $i <= 3; $i++) {
        $stmt3->bindValue(':val', $i);
        $stmt3->execute();

        $row1 = $stmt3->fetch();
        if (!array_key_exists('col_a', $row1) || !array_key_exists('col_b', $row1)) {
            throw new RuntimeException("Test 3 iteration $i: RS1 missing expected columns.");
        }
        if ($row1['col_a'] != $i) {
            throw new RuntimeException("Test 3 iteration $i: RS1 col_a expected $i, got {$row1['col_a']}.");
        }

        $stmt3->nextRowset();
        $row2 = $stmt3->fetch();
        if (!array_key_exists('col_x', $row2) || !array_key_exists('col_y', $row2)) {
            throw new RuntimeException("Test 3 iteration $i: RS2 missing expected columns.");
        }
        if ($row2['col_x'] != $i) {
            throw new RuntimeException("Test 3 iteration $i: RS2 col_x expected $i, got {$row2['col_x']}.");
        }

        $stmt3->closeCursor();
    }
    echo "Test 3 passed: same column count, different names\n";

    echo "Done\n";
} catch (Exception $e) {
    echo $e->getMessage() . "\n";
}
?>
--EXPECT--
Test 1 passed: re-execute with closeCursor
Test 2 passed: re-execute without closeCursor
Test 3 passed: same column count, different names
Done
