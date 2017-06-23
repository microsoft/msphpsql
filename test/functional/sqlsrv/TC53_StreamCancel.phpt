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
include 'MsCommon.inc';

function CancelStream()
{
    include 'MsSetup.inc';

    $testName = "Stream - Cancel";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    $noRows = 5;
    CreateTable($conn1, $tableName);
    InsertRows($conn1, $tableName, $noRows);

    $stmt1 = SelectFromTable($conn1, $tableName);

    // Expired stream
    $stream1 = GetStream($stmt1);
    sqlsrv_fetch($stmt1);
    CheckStream($stream1);

    // Cancelled statement
    $stream2 = GetStream($stmt1);
    sqlsrv_cancel($stmt1);
    CheckStream($stream2);

    // Closed statement
    $stmt2 = SelectFromTable($conn1, $tableName);
    $stream3 = GetStream($stmt2);
    sqlsrv_free_stmt($stmt2);
    CheckStream($stream3);
    
    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);

    EndTest($testName);
}

function GetStream($stmt)
{
    $stream = null;

    if (!sqlsrv_fetch($stmt))
    {
        FatalError("Failed to fetch row ".$row);
    }
    while ($stream == null)
    {
        $col = rand(11, 22);    // select a streamable field
        if (!IsStreamable($col + 1))
        {
            die("Failed to select a streamable field.");
        }
        $type = GetSqlType($col  + 1);
        Trace("Selected streamable type: $type ...\n");

        $stream = sqlsrv_get_field($stmt, $col, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        if ($stream === false)
        {
            FatalError("Failed to read field $col: $type");
        }
    }

    return ($stream);
}

function CheckStream($stream)
{
    $bytesRead = 0;
    try
    {
        $value = fread($stream, 8192);
        $bytesRread = strlen($value);
    }
    catch (Exception $e)
    {
        $bytesRead = 0;
        Trace($e->getMessage());
    }
    if ($bytesRead > 0)
    {
        die("Invalid stream should not return any data.");
    }
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        CancelStream();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTREGEX--

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 86

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 86

|Warning: fread\(\): supplied argument is not a valid stream resource in .+\\TC53_StreamCancel.php on line 86|Warning: fread\(\): expects parameter 1 to be resource, null given in .+\\TC53_StreamCancel.php on line 86
Test "Stream - Cancel" completed successfully.

