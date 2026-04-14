--TEST--
PDO nextRowset continues after mid-batch error with XACT_ABORT OFF
--DESCRIPTION--
When a multi-statement batch contains a failing statement (e.g. divide-by-zero)
with XACT_ABORT OFF, nextRowset() should report the error but still allow
subsequent nextRowset() calls to reach the remaining result sets.  Previously
the driver called SQLCancel() which destroyed the remaining batch.

This test verifies both ERRMODE_WARNING and ERRMODE_EXCEPTION modes.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$batch = "SET XACT_ABORT OFF; SELECT 1 AS n; SELECT 1/0 AS boom; SELECT 2 AS n;";

// ============================================================
// Test 1: ERRMODE_WARNING — error reported, batch continues
// ============================================================
echo "=== Test 1: ERRMODE_WARNING ===\n";

try {
    $conn = connect("", array(), PDO::ERRMODE_WARNING);
    $stmt = $conn->query($batch);

    // Result set 1
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 1: n={$row['n']}\n";

    // Advance past failing SELECT 1/0 — returns true (batch not aborted)
    $next = $stmt->nextRowset();
    echo "nextRowset (failing): ";
    var_dump($next);

    $err = $stmt->errorInfo();
    echo "Error SQLSTATE: {$err[0]}\n";

    // Advance to result set 3
    $next2 = $stmt->nextRowset();
    echo "nextRowset (SELECT 2): ";
    var_dump($next2);

    $row2 = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 3: n={$row2['n']}\n";

    $stmt = null;
    $conn = null;
} catch (PDOException $e) {
    echo "UNEXPECTED Exception: " . $e->getMessage() . "\n";
}

// ============================================================
// Test 2: ERRMODE_EXCEPTION — exception cleared internally,
//         batch remains navigable, error in errorInfo()
// ============================================================
echo "\n=== Test 2: ERRMODE_EXCEPTION ===\n";

try {
    $conn = connect("", array(), PDO::ERRMODE_EXCEPTION);
    $stmt = $conn->query($batch);

    // Result set 1
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 1: n={$row['n']}\n";

    // Advance past failing SELECT 1/0 — the driver clears the pending
    // exception internally so that the batch remains navigable.
    $next = $stmt->nextRowset();
    echo "nextRowset (failing): ";
    var_dump($next);

    // Error info is still populated even though the exception was cleared.
    $err = $stmt->errorInfo();
    echo "Error SQLSTATE: {$err[0]}\n";

    // Advance to result set 3
    $next2 = $stmt->nextRowset();
    echo "nextRowset (SELECT 2): ";
    var_dump($next2);

    $row2 = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 3: n={$row2['n']}\n";

    $stmt = null;
    $conn = null;
} catch (PDOException $e) {
    echo "UNEXPECTED Exception: " . $e->getMessage() . "\n";
}

echo "\nDone\n";
?>
--EXPECTF--
=== Test 1: ERRMODE_WARNING ===
Result set 1: n=1
nextRowset (failing): bool(true)
Error SQLSTATE: 22012
nextRowset (SELECT 2): bool(true)
Result set 3: n=2

=== Test 2: ERRMODE_EXCEPTION ===
Result set 1: n=1
nextRowset (failing): bool(true)
Error SQLSTATE: 22012
nextRowset (SELECT 2): bool(true)
Result set 3: n=2

Done
