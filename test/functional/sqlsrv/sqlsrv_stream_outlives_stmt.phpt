--TEST--
GitHub issue 1443 - stream remains valid after statement goes out of scope
--DESCRIPTION--
When a stream is returned from a function where the statement variable goes out
of scope, the stream should remain valid as long as it has references. Previously
the statement destructor would close the stream, making it invalid.
Also validates that both the stream and statement are properly cleaned up after use,
with no resource leaks detected via memory_get_usage over repeated iterations.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

// Test 1: Stream returned from a function where $stmt goes out of scope
function getStream($conn)
{
    $stmt = sqlsrv_query($conn, "SELECT CONVERT(VARBINARY(32), 0x48656C6C6F)");
    if ($stmt === false) {
        fatalError("Query failed in getStream.");
    }
    sqlsrv_fetch($stmt);
    // $stmt will go out of scope when this function returns
    return sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
}

$conn = connect();
if ($conn === false) {
    fatalError("Could not connect.");
}

$stream = getStream($conn);
if ($stream === false) {
    fatalError("Failed to get stream.");
}

// The stream should still be valid even though $stmt went out of scope
$data = fread($stream, 100);
if ($data === false) {
    fatalError("fread failed on stream.");
}

// CONVERT(VARBINARY(32), 0x48656C6C6F) should return the bytes "Hello"
if ($data === "Hello") {
    echo "Test 1 passed: stream data read correctly after stmt out of scope.\n";
} else {
    echo "Test 1 FAILED: expected 'Hello', got '" . bin2hex($data) . "'.\n";
}

fclose($stream);

// Test 2: Stream from sqlsrv_prepare + sqlsrv_execute
function getStreamPrepared($conn)
{
    $stmt = sqlsrv_prepare($conn, "SELECT CONVERT(VARBINARY(32), 0x576F726C64)");
    if ($stmt === false) {
        fatalError("Prepare failed in getStreamPrepared.");
    }
    sqlsrv_execute($stmt);
    sqlsrv_fetch($stmt);
    return sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
}

$stream2 = getStreamPrepared($conn);
if ($stream2 === false) {
    fatalError("Failed to get stream from prepared stmt.");
}

$data2 = fread($stream2, 100);
if ($data2 === "World") {
    echo "Test 2 passed: stream from prepared stmt works after stmt out of scope.\n";
} else {
    echo "Test 2 FAILED: expected 'World', got '" . bin2hex($data2) . "'.\n";
}

fclose($stream2);

// Test 3: Multiple reads from the stream
function getStreamLargeData($conn)
{
    $stmt = sqlsrv_query($conn, "SELECT CONVERT(VARBINARY(MAX), REPLICATE(CONVERT(VARBINARY(MAX), 0x41), 1000))");
    if ($stmt === false) {
        fatalError("Query failed in getStreamLargeData.");
    }
    sqlsrv_fetch($stmt);
    return sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
}

$stream3 = getStreamLargeData($conn);
if ($stream3 === false) {
    fatalError("Failed to get stream for large data.");
}

$allData = '';
while (!feof($stream3)) {
    $chunk = fread($stream3, 100);
    if ($chunk === false) {
        break;
    }
    $allData .= $chunk;
}

if (strlen($allData) === 1000 && $allData === str_repeat('A', 1000)) {
    echo "Test 3 passed: large stream data read correctly.\n";
} else {
    echo "Test 3 FAILED: expected 1000 bytes of 'A', got " . strlen($allData) . " bytes.\n";
}

fclose($stream3);

// Test 4: Verify cleanup - no resource leak over repeated iterations.
// Run the stream-outlives-stmt pattern in a loop and check that memory
// usage stabilizes, confirming the statement is freed when the stream closes.
$leakDetected = false;
for ($i = 0; $i < 20; $i++) {
    $s = getStream($conn);
    $d = fread($s, 100);
    fclose($s);
    unset($s);
    unset($d);

    if ($i === 0) {
        $memBaseline = memory_get_usage();
    }
}
$memAfter = memory_get_usage();
// Allow a small tolerance (32 KB) for PHP internal allocations.
// A real leak would grow ~proportionally to iteration count.
if (($memAfter - $memBaseline) < 32768) {
    echo "Test 4 passed: no resource leak detected over repeated iterations.\n";
} else {
    echo "Test 4 FAILED: possible leak, memory grew by " . ($memAfter - $memBaseline) . " bytes.\n";
}

// Test 5: After all streams are closed, the connection should still be
// fully functional — proving statements were cleaned up properly.
$stmt = sqlsrv_query($conn, "SELECT 1 AS alive");
if ($stmt === false) {
    echo "Test 5 FAILED: connection unusable after cleanup.\n";
} else {
    sqlsrv_fetch($stmt);
    $val = sqlsrv_get_field($stmt, 0);
    if ($val == 1) {
        echo "Test 5 passed: connection works after all streams and statements cleaned up.\n";
    } else {
        echo "Test 5 FAILED: unexpected value $val.\n";
    }
    sqlsrv_free_stmt($stmt);
}

sqlsrv_close($conn);
echo "Done.\n";
?>
--EXPECT--
Test 1 passed: stream data read correctly after stmt out of scope.
Test 2 passed: stream from prepared stmt works after stmt out of scope.
Test 3 passed: large stream data read correctly.
Test 4 passed: no resource leak detected over repeated iterations.
Test 5 passed: connection works after all streams and statements cleaned up.
Done.
