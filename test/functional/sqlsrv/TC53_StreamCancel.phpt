--TEST--
Stream Cancel Test
--DESCRIPTION--
Verifies that a stream is invalidated by:
    - fetching next row
    - cancelling or closing the statement
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function CancelStream()
{
    include 'MsSetup.inc';

    $testName = "Stream - Cancel";
    startTest($testName);

    setup();
    $conn1 = connect();

    $noRows = 5;
    createTable($conn1, $tableName);
    insertRows($conn1, $tableName, $noRows);

    $stmt1 = selectFromTable($conn1, $tableName);

    // Expired stream
    $stream1 = GetStream($stmt1);
    sqlsrv_fetch($stmt1);
    CheckStream($stream1);

    // Cancelled statement
    $stream2 = GetStream($stmt1);
    sqlsrv_cancel($stmt1);
    CheckStream($stream2);

    // Closed statement
    $stmt2 = selectFromTable($conn1, $tableName);
    $stream3 = GetStream($stmt2);
    sqlsrv_free_stmt($stmt2);
    CheckStream($stream3);

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function GetStream($stmt)
{
    $stream = null;

    if (!sqlsrv_fetch($stmt)) {
        fatalError("Failed to fetch row ".$row);
    }
    while ($stream == null) {
        $col = rand(11, 22);    // select a streamable field
        if (!IsStreamable($col + 1)) {
            die("Failed to select a streamable field.");
        }
        $type = GetSqlType($col  + 1);
        trace("Selected streamable type: $type ...\n");

        $stream = sqlsrv_get_field($stmt, $col, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        if ($stream === false) {
            fatalError("Failed to read field $col: $type");
        }
    }

    return ($stream);
}

function CheckStream($stream)
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

//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        CancelStream();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}

repro();

?>
--EXPECTREGEX--

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 86

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 86

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 86
Test "Stream - Cancel" completed successfully.
