--TEST--
GitHub issue 1466 - Re-executing prepared statement with multiple result sets
--DESCRIPTION--
Verifies that re-executing a prepared statement returning multiple result sets
does not cause a fatal error or return corrupted data.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = connect();
if ($conn === false) {
    fatalError("Could not connect.\n");
}

$sql = "
    SET NOCOUNT ON;
    DECLARE @id INT = ?;
    -- Result set 1
    SELECT @id AS r1col1, 2 AS r1col2, 3 AS r1col3, N'Test' AS r1col4
    WHERE @id IN (2, 3);
    -- Result set 2
    SELECT @id AS r2col1, N'/test.txt' AS r2col2, N'text/plain' AS r2col3
    WHERE @id IN (2, 3);
";

$stmt = sqlsrv_prepare($conn, $sql, array(&$id));
if ($stmt === false) {
    fatalError("sqlsrv_prepare failed.\n");
}

$idList = array(2, 1, 3);
foreach ($idList as $id) {
    if (!sqlsrv_execute($stmt)) {
        fatalError("sqlsrv_execute failed for id=$id.\n");
    }

    // Fetch result set 1
    $rowCount1 = 0;
    while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
        if (!array_key_exists('r1col1', $row)) {
            fatalError("Result set 1 row missing expected column 'r1col1'.\n");
        }
        if ($row['r1col1'] != $id) {
            fatalError("Result set 1 r1col1 expected $id, got {$row['r1col1']}.\n");
        }
        $rowCount1++;
    }

    // Move to result set 2
    $nextResult = sqlsrv_next_result($stmt);
    if ($nextResult === false) {
        fatalError("sqlsrv_next_result failed for id=$id.\n");
    }

    $rowCount2 = 0;
    if ($nextResult !== null) {
        while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
            if (!array_key_exists('r2col1', $row)) {
                fatalError("Result set 2 row missing expected column 'r2col1'.\n");
            }
            if ($row['r2col1'] != $id) {
                fatalError("Result set 2 r2col1 expected $id, got {$row['r2col1']}.\n");
            }
            $rowCount2++;
        }
    }

    // Verify row counts based on input
    if ($id == 1) {
        if ($rowCount1 !== 0 || $rowCount2 !== 0) {
            fatalError("Expected empty result sets for id=1.\n");
        }
    } else {
        if ($rowCount1 !== 1 || $rowCount2 !== 1) {
            fatalError("Expected one row per result set for id=$id.\n");
        }
    }
}
sqlsrv_free_stmt($stmt);
echo "Test 1 passed: re-execute with next_result\n";

// Test 2: Re-execute without consuming all result sets (auto-flush)
// This exercises the flush loop in sqlsrv_execute that calls
// core_sqlsrv_next_result before the new execute.
$id = 2;
$stmt2 = sqlsrv_prepare($conn, $sql, array(&$id));
if ($stmt2 === false) {
    fatalError("sqlsrv_prepare failed for test 2.\n");
}
sqlsrv_execute($stmt2);
$row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
// Do NOT call sqlsrv_next_result -- just re-execute immediately
$id = 3;
if (!sqlsrv_execute($stmt2)) {
    fatalError("sqlsrv_execute failed for test 2 re-execute.\n");
}
$row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
if (!$row || !array_key_exists('r1col1', $row) || $row['r1col1'] != 3) {
    fatalError("Test 2 failed: unexpected result after re-execute without next_result.\n");
}
sqlsrv_free_stmt($stmt2);
echo "Test 2 passed: re-execute without next_result\n";

// Test 3: Re-execute when both result sets have the same number of columns
// but different column names.
$sql3 = "
    SET NOCOUNT ON;
    DECLARE @v INT = ?;
    SELECT @v AS col_a, N'first' AS col_b;
    SELECT @v AS col_x, N'second' AS col_y;
";
$val = 0;
$stmt3 = sqlsrv_prepare($conn, $sql3, array(&$val));
if ($stmt3 === false) {
    fatalError("sqlsrv_prepare failed for test 3.\n");
}
for ($val = 1; $val <= 3; $val++) {
    if (!sqlsrv_execute($stmt3)) {
        fatalError("sqlsrv_execute failed for test 3, val=$val.\n");
    }
    $row1 = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC);
    if (!$row1 || !array_key_exists('col_a', $row1) || $row1['col_a'] != $val) {
        fatalError("Test 3 val=$val: RS1 unexpected result.\n");
    }
    $next = sqlsrv_next_result($stmt3);
    if ($next === false) {
        fatalError("sqlsrv_next_result failed for test 3, val=$val.\n");
    }
    $row2 = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC);
    if (!$row2 || !array_key_exists('col_x', $row2) || $row2['col_x'] != $val) {
        fatalError("Test 3 val=$val: RS2 unexpected result.\n");
    }
}
sqlsrv_free_stmt($stmt3);
echo "Test 3 passed: same column count, different names\n";

sqlsrv_close($conn);
echo "Done\n";
?>
--EXPECT--
Test 1 passed: re-execute with next_result
Test 2 passed: re-execute without next_result
Test 3 passed: same column count, different names
Done
