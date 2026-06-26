--TEST--
sqlsrv_next_result continues after mid-batch error with XACT_ABORT OFF
--DESCRIPTION--
When a multi-statement batch contains a failing statement (RAISERROR severity 16)
with XACT_ABORT OFF, legacy behavior should remain the default so that
sqlsrv_next_result() fails on the mid-batch error.  When BatchErrorContinue is
enabled explicitly, the driver should report the error but still allow the user
to advance to subsequent result sets.

This test also verifies:
- Error information is available via sqlsrv_errors()
- Re-executing the statement (flush loop) works after batch errors

Note: We use RAISERROR (severity 16) instead of SELECT 1/0 because
divide-by-zero with ANSI_WARNINGS ON (the default) produces
SQL_SUCCESS_WITH_INFO rather than SQL_ERROR, making it unreliable as a
cross-environment SQL_ERROR trigger.
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

$batch = "SET XACT_ABORT OFF; SELECT 1 AS n; RAISERROR('batch_error_test', 16, 1); SELECT 2 AS n;";

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

// Advance past RAISERROR — legacy behavior returns false
$next = sqlsrv_next_result($stmt);
echo "next_result (failing): ";
var_dump($next);

// Error is available via sqlsrv_errors()
$errors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
echo "Error captured: ";
echo ($errors && isset($errors[0]['SQLSTATE'])) ? "yes\n" : "no\n";

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
echo "Error captured: ";
echo ($errors && isset($errors[0]['SQLSTATE'])) ? "yes\n" : "no\n";

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
// Test 3: Default re-execute keeps silent flush behavior
// ============================================================
echo "\n=== Test 3: Default re-execute silent flush ===\n";

$stmt2 = sqlsrv_prepare($conn, $batch);
if ($stmt2 === false) {
    fatalError("sqlsrv_prepare failed.\n");
}

// First execution — consume only the first result set
sqlsrv_execute($stmt2);
$row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
echo "First execute, result set 1: n={$row['n']}\n";

// Re-execute — the internal flush loop must handle the remaining
// results (including the SQL_ERROR from RAISERROR) without failing and
// without pushing diagnostics to sqlsrv_errors().
$ok = sqlsrv_execute($stmt2);
echo "Re-execute: ";
var_dump($ok);

$postExecErrors = sqlsrv_errors(SQLSRV_ERR_ERRORS);
echo "Errors after re-execute: ";
var_dump($postExecErrors);

// Verify the new execution produces correct results
$row = sqlsrv_fetch_array($stmt2, SQLSRV_FETCH_ASSOC);
echo "Second execute, result set 1: n={$row['n']}\n";

sqlsrv_free_stmt($stmt2);

// ============================================================
// Test 4: Opt-in re-execute still succeeds
// ============================================================
echo "\n=== Test 4: Opt-in re-execute ===\n";

$stmt3 = sqlsrv_prepare($connOptIn, $batch);
if ($stmt3 === false) {
    fatalError("sqlsrv_prepare failed for opt-in re-execute.\n");
}

sqlsrv_execute($stmt3);
$row = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC);
echo "First execute, result set 1: n={$row['n']}\n";

$ok = sqlsrv_execute($stmt3);
echo "Re-execute: ";
var_dump($ok);

$row = sqlsrv_fetch_array($stmt3, SQLSRV_FETCH_ASSOC);
echo "Second execute, result set 1: n={$row['n']}\n";

sqlsrv_free_stmt($stmt3);
sqlsrv_close($connOptIn);
sqlsrv_close($conn);

echo "\nDone\n";
?>
--EXPECT--
=== Test 1: Default behavior ===
Result set 1: n=1
next_result (failing): bool(false)
Error captured: yes

=== Test 2: Opt-in behavior ===
Result set 1: n=1
next_result (failing): bool(true)
Error captured: yes
next_result (SELECT 2): bool(true)
Result set 3: n=2
next_result (end): NULL

=== Test 3: Default re-execute silent flush ===
First execute, result set 1: n=1
Re-execute: bool(true)
Errors after re-execute: NULL
Second execute, result set 1: n=1

=== Test 4: Opt-in re-execute ===
First execute, result set 1: n=1
Re-execute: bool(true)
Second execute, result set 1: n=1

Done
