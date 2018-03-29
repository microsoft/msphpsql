--TEST--
Stream Read Test
--DESCRIPTION--
Verifies that all SQL types defined as capable of streaming (13 types)
can be successfully retrieved as streams.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?// locale must be set before 1st connection
setUSAnsiLocale();
require('skipif_versions_old.inc');
?>
--FILE--
<?php
require_once('MsCommon.inc');

function streamRead($noRows, $startRow)
{
    setup();
    $tableName = 'TC51test';
    if (useUTF8Data()) {
        $conn1 = AE\connect(array( 'CharacterSet'=>'UTF-8' ));
    } else {
        $conn1 = AE\connect();
    }

    AE\createTestTable($conn1, $tableName);
    AE\insertTestRowsByRange($conn1, $tableName, $startRow, $startRow + $noRows - 1);

    $query = "SELECT * FROM [$tableName] ORDER BY c27_timestamp";
    $stmt1 = AE\executeQuery($conn1, $query);
    $numFields = sqlsrv_num_fields($stmt1);

    for ($row = 1; $row <= $noRows; $row++) {
        if (!sqlsrv_fetch($stmt1)) {
            fatalError("Failed to fetch row ".$row);
        }
        trace("\nStreaming row $row:\n");
        for ($j = 0; $j < $numFields; $j++) {
            $col = $j + 1;
            if (isUpdatable($col)) {
                if (isStreamable($col)) {
                    verifyStream($stmt1, $startRow + $row - 1, $j);
                }
            }
        }
    }

    sqlsrv_free_stmt($stmt1);

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
}

function verifyStream($stmt, $row, $colIndex)
{
    $col = $colIndex + 1;
    if (isStreamable($col)) {
        $type = getSqlType($col);
        if (isBinary($col)) {
            $stream = sqlsrv_get_field($stmt, $colIndex, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        } else {
            if (useUTF8Data()) {
                $stream = sqlsrv_get_field($stmt, $colIndex, SQLSRV_PHPTYPE_STREAM('UTF-8'));
            } else {
                $stream = sqlsrv_get_field($stmt, $colIndex, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
            }
        }
        if ($stream === false) {
            fatalError("Failed to read field $col: $type");
        } else {
            $value = '';
            if ($stream) {
                while (!feof($stream)) {
                    $value .= fread($stream, 8192);
                }
                fclose($stream);
                $data = AE\getInsertData($row, $col);
                if (!checkData($col, $value, $data)) {
                    trace("Data corruption on row $row column $col\n");
                    die("Data corruption on row $row column $col\n");
                }
            }
            traceData($type, "".strlen($value)." bytes");
        }
    }
}

function checkData($col, $actual, $expected)
{
    $success = true;

    if (isBinary($col)) {
        $actual = bin2hex($actual);
        if (strncasecmp($actual, $expected, strlen($expected)) != 0) {
            $success = false;
        }
    } else {
        if (strncasecmp($actual, $expected, strlen($expected)) != 0) {
            if ($col != 19) {    // skip ntext
                $pos = strpos($actual, $expected);
                if (($pos === false) || ($pos > 1)) {
                    $success = false;
                }
            }
        }
    }
    if (!$success) {
        trace("\nData error\nExpected:\n$expected\nActual:\n$actual\n");
    }

    return ($success);
}

// locale must be set before 1st connection
setUSAnsiLocale();
global $testName;
$testName = "Stream - Read";

// test ansi only if windows or non-UTF8 locales are supported (ODBC 17 and above)
startTest($testName);
if (isLocaleSupported()) {
    try {
        setUTF8Data(false);
        streamRead(20, 1);
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}
endTest($testName);

// test utf8 
startTest($testName);
try {
    setUTF8Data(true);
    resetLocaleToDefault();
    streamRead(20, 1);
} catch (Exception $e) {
    echo $e->getMessage();
}
endTest($testName);

?>
--EXPECT--
Test "Stream - Read" completed successfully.
Test "Stream - Read" completed successfully.
