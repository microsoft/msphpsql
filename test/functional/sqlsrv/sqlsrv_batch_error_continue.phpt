--TEST--
sqlsrv_next_result continues after mid-batch error with XACT_ABORT OFF
--DESCRIPTION--
When a multi-statement batch contains a failing statement (e.g. divide-by-zero)
with XACT_ABORT OFF, legacy behavior should remain the default so that
sqlsrv_next_result() fails on the mid-batch error.  When BatchErrorContinue is
enabled explicitly, the driver should report the error but still allow the user
to advance to subsequent result sets.

This test also verifies:
- Error information is available via sqlsrv_errors()
- Re-executing the statement (flush loop) works after batch errors when the
    opt-in behavior is enabled
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon.inc");

$conn = connect();
if ($conn === false) {
    fatalError("Could not connect.\n");
}

$batch = "SET XACT_ABORT OFF; SELECT 1 AS n; SELECT 1/0 AS boom; SELECT 2 AS n;";

// ============================================================
// Test 1: Default behavior remains unchanged
// ============================================================
echo "=== Test 1: Default behavior ===\n";

$stmt = sqlsrv_query($conn, $batch);
if ($stmt === false) {
    fatalError("sqlsrv_query failed.\n");
}

// Result set 1: SELECT 1
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Result set 1: n={$row['n']}\n";

// Advance past failing SELECT 1/0 — legacy behavior returns false
$next = sqlsrv_next_result($stmt);
echo "next_result (failing): ";
var_dump($next);

// Error is available via sqlsrv_errors()
$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
if ($errors) {
    echo "Error SQLSTATE: {$errors[0]['SQLSTATE']}\n";
}

sqlsrv_free_stmt($stmt);

// ============================================================
// Test 2: Opt-in behavior continues after the error
// ============================================================
echo "\n=== Test 2: Opt-in behavior ===\n";

$connOptIn = connect(array('BatchErrorContinue' => true));
if ($connOptIn === false) {
    fatalError("Could not connect with BatchErrorContinue.\n");
}

$stmt = sqlsrv_query($connOptIn, $batch);
if ($stmt === false) {
    fatalError("sqlsrv_query failed for opt-in test.\n");
}

$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Result set 1: n={$row['n']}\n";

$next = sqlsrv_next_result($stmt);
echo "next_result (failing): ";
var_dump($next);

$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
if ($errors) {
    echo "Error SQLSTATE: {$errors[0]['SQLSTATE']}\n";
}

$next2 = sqlsrv_next_result($stmt);
echo "next_result (SELECT 2): ";
var_dump($next2);

$row2 = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
echo "Result set 3: n={$row2['n']}\n";

$next3 = sqlsrv_next_result($stmt);
echo "next_result (end): ";
var_dump($next3);

sqlsrv_free_stmt($stmt);

// ============================================================
// Test 3: Re-execute after mid-batch error (flush loop)
// ============================================================
echo "\n=== Test 3: Re-execute after error ===\n";

$stmt2 = sqlsrv_prepare($connOptIn, $batch);
if ($stmt2 === false) {
    fatalError("sqlsrv_prepare failed.\n");
}

// First execution — consume only the first result set
sqlsrv_execute($stmt2);
$row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
echo "First execute, result set 1: n={$row['n']}\n";

// Re-execute — the internal flush loop must handle the remaining
// results (including the SQL_ERROR from SELECT 1/0) without failing.
$ok = sqlsrv_execute($stmt2);
echo "Re-execute: ";
var_dump($ok);

// Verify the new execution produces correct results
$row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
echo "Second execute, result set 1: n={$row['n']}\n";

sqlsrv_free_stmt($stmt2);
sqlsrv_close($connOptIn);
sqlsrv_close($conn);

echo "\nDone\n";
?>
--EXPECT--
=== Test 1: Default behavior ===
Result set 1: n=1
next_result (failing): bool(false)
Error SQLSTATE: 22012

=== Test 2: Opt-in behavior ===
Result set 1: n=1
next_result (failing): bool(true)
Error SQLSTATE: 22012
next_result (SELECT 2): bool(true)
Result set 3: n=2
next_result (end): NULL

=== Test 3: Re-execute after error ===
First execute, result set 1: n=1
Re-execute: bool(true)
Second execute, result set 1: n=1

Done
