--TEST--
Stream Cancel Test
--DESCRIPTION--
Verifies that a stream is invalidated by:
    - fetching next row
    - cancelling or closing the statement
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function CancelStream()
{
    $testName = "Stream - Cancel";
    startTest($testName);

    setup();
    $tableName = "TC53test";
    $conn1 = AE\connect();

    $noRows = 5;
    AE\createTestTable($conn1, $tableName);
    AE\insertTestRows($conn1, $tableName, $noRows);

    $stmt1 = AE\selectFromTable($conn1, $tableName);

    // Expired stream
    $stream1 = getStream($stmt1);
    sqlsrv_fetch($stmt1);
    checkStream($stream1);

    // Cancelled statement
    $stream2 = getStream($stmt1);
    sqlsrv_cancel($stmt1);
    checkStream($stream2);

    // Closed statement
    $stmt2 = AE\selectFromTable($conn1, $tableName);
    $stream3 = getStream($stmt2);
    sqlsrv_free_stmt($stmt2);
    checkStream($stream3);

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function getStream($stmt)
{
    $stream = null;

    if (!sqlsrv_fetch($stmt)) {
        fatalError("Failed to fetch row ".$row);
    }
    while ($stream == null) {
        $col = rand(11, 22);    // select a streamable field
        if (!isStreamable($col + 1)) {
            die("Failed to select a streamable field.");
        }
        $type = getSqlType($col  + 1);
        trace("Selected streamable type: $type ...\n");

        $stream = sqlsrv_get_field($stmt, $col, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        if ($stream === false) {
            fatalError("Failed to read field $col: $type");
        }
    }

    return ($stream);
}

function checkStream($stream)
{
    $bytesRead = 0;
    try {
        $value = fread($stream, 8192);
        $bytesRread = strlen($value);
    } catch (Exception $e) {
        $bytesRead = 0;
        trace($e->getMessage());
    }
    if ($bytesRead > 0) {
        die("Invalid stream should not return any data.");
    }
}

try {
    CancelStream();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECTREGEX--

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 84

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 84

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 84
Test "Stream - Cancel" completed successfully.
