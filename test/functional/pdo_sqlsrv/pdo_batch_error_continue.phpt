--TEST--
PDO nextRowset continues after mid-batch error with XACT_ABORT OFF
--DESCRIPTION--
When a multi-statement batch contains a failing statement (e.g. divide-by-zero)
with XACT_ABORT OFF, legacy behavior should remain the default so that
nextRowset() fails in warning mode and throws in exception mode.  When
SQLSRV_ATTR_BATCH_ERROR_CONTINUE is enabled explicitly, nextRowset() should
report the error but still allow subsequent nextRowset() calls to reach the
remaining result sets.

This test verifies both ERRMODE_WARNING and ERRMODE_EXCEPTION modes for the
default and opt-in behaviors. It also verifies that opt-in can be set at
connection construction time via PDO driver options.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$batch = "SET XACT_ABORT OFF; SELECT 1 AS n; SELECT 1/0 AS boom; SELECT 2 AS n;";

// ============================================================
// Test 1: Default ERRMODE_WARNING behavior
// ============================================================
echo "=== Test 1: Default ERRMODE_WARNING ===\n";

try {
    $conn = connect("", array(), PDO::ERRMODE_WARNING);
    $stmt = $conn->query($batch);

    // Result set 1
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 1: n={$row['n']}\n";

    // Default behavior: advance past failing SELECT 1/0 returns false
    $next = $stmt->nextRowset();
    echo "nextRowset (failing): ";
    var_dump($next);

    $err = $stmt->errorInfo();
    echo "Error captured: ";
    echo (!empty($err[0])) ? "yes\n" : "no\n";

    $stmt = null;
    $conn = null;
} catch (PDOException $e) {
    echo "UNEXPECTED Exception: " . $e->getMessage() . "\n";
}

// ============================================================
// Test 2: Default ERRMODE_EXCEPTION behavior
// ============================================================
echo "\n=== Test 2: Default ERRMODE_EXCEPTION ===\n";

try {
    $conn = connect("", array(), PDO::ERRMODE_EXCEPTION);
    $stmt = $conn->query($batch);

    // Result set 1
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 1: n={$row['n']}\n";

    $stmt->nextRowset();
} catch (PDOException $e) {
    echo "Caught error: yes\n";
}

// ============================================================
// Test 3: Opt-in ERRMODE_WARNING behavior
// ============================================================
echo "\n=== Test 3: Opt-in ERRMODE_WARNING ===\n";

try {
    $conn = connect("", array(), PDO::ERRMODE_WARNING);
    $conn->setAttribute(PDO::SQLSRV_ATTR_BATCH_ERROR_CONTINUE, true);
    $stmt = $conn->query($batch);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 1: n={$row['n']}\n";

    $next = $stmt->nextRowset();
    echo "nextRowset (failing): ";
    var_dump($next);

    $err = $stmt->errorInfo();
    echo "Error captured: ";
    echo (!empty($err[0])) ? "yes\n" : "no\n";

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
// Test 4: Opt-in ERRMODE_EXCEPTION behavior
// ============================================================
echo "\n=== Test 4: Opt-in ERRMODE_EXCEPTION ===\n";

try {
    $conn = connect("", array(), PDO::ERRMODE_EXCEPTION);
    $conn->setAttribute(PDO::SQLSRV_ATTR_BATCH_ERROR_CONTINUE, true);
    $stmt = $conn->query($batch);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 1: n={$row['n']}\n";

    $next = $stmt->nextRowset();
    echo "nextRowset (failing): ";
    var_dump($next);

    $err = $stmt->errorInfo();
    echo "Error captured: ";
    echo (!empty($err[0])) ? "yes\n" : "no\n";

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
// Test 5: Opt-in via PDO constructor options
// ============================================================
echo "\n=== Test 5: Constructor opt-in ===\n";

try {
    $conn = connect("", array(PDO::SQLSRV_ATTR_BATCH_ERROR_CONTINUE => true), PDO::ERRMODE_WARNING);
    $stmt = $conn->query($batch);

    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "Result set 1: n={$row['n']}\n";

    $next = $stmt->nextRowset();
    echo "nextRowset (failing): ";
    var_dump($next);

    $err = $stmt->errorInfo();
    echo "Error captured: ";
    echo (!empty($err[0])) ? "yes\n" : "no\n";

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
=== Test 1: Default ERRMODE_WARNING ===
Result set 1: n=1
nextRowset (failing): bool(false)
Error captured: yes

=== Test 2: Default ERRMODE_EXCEPTION ===
Result set 1: n=1
Caught error: yes

=== Test 3: Opt-in ERRMODE_WARNING ===
Result set 1: n=1
nextRowset (failing): bool(true)
Error captured: yes
nextRowset (SELECT 2): bool(true)
Result set 3: n=2

=== Test 4: Opt-in ERRMODE_EXCEPTION ===
Result set 1: n=1
nextRowset (failing): bool(true)
Error captured: yes
nextRowset (SELECT 2): bool(true)
Result set 3: n=2

=== Test 5: Constructor opt-in ===
Result set 1: n=1
nextRowset (failing): bool(true)
Error captured: yes
nextRowset (SELECT 2): bool(true)
Result set 3: n=2

Done
