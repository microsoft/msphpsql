--TEST--
GitHub issue 1443 - stream remains valid after statement goes out of scope
--DESCRIPTION--
When a stream is returned from a function where the statement variable goes out
of scope, the stream should remain valid as long as it has references. Previously
the statement destructor would close the stream, making it invalid.
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

sqlsrv_close($conn);
echo "Done.\n";
?>
--EXPECT--
Test 1 passed: stream data read correctly after stmt out of scope.
Test 2 passed: stream from prepared stmt works after stmt out of scope.
Test 3 passed: large stream data read correctly.
Done.
