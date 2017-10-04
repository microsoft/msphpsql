--TEST--
Stream Read Test
--DESCRIPTION--
Verifies that all SQL types defined as capable of streaming (13 types)
can be successfully retrieved as streams.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function StreamRead($noRows, $startRow)
{
    include 'MsSetup.inc';

    $testName = "Stream - Read";
    startTest($testName);

    setup();
    if (! isWindows()) {
        $conn1 = connect(array( 'CharacterSet'=>'UTF-8' ));
    } else {
        $conn1 = connect();
    }

    createTable($conn1, $tableName);
    insertRowsByRange($conn1, $tableName, $startRow, $startRow + $noRows - 1);

    $query = "SELECT * FROM [$tableName] ORDER BY c27_timestamp";
    $stmt1 = selectQuery($conn1, $query);
    $numFields = sqlsrv_num_fields($stmt1);

    for ($row = 1; $row <= $noRows; $row++) {
        if (!sqlsrv_fetch($stmt1)) {
            fatalError("Failed to fetch row ".$row);
        }
        trace("\nStreaming row $row:\n");
        $skipCount = 0;
        for ($j = 0; $j < $numFields; $j++) {
            $col = $j + 1;
            if (!IsUpdatable($col)) {
                $skipCount++;
            }
            if (IsStreamable($col)) {
                VerifyStream($stmt1, $startRow + $row - 1, $j, $skipCount);
            }
        }
    }

    sqlsrv_free_stmt($stmt1);

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

function VerifyStream($stmt, $row, $colIndex, $skip)
{
    $col = $colIndex + 1;
    if (IsStreamable($col)) {
        $type = GetSqlType($col);
        if (IsBinary($col)) {
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
                $data = getInsertData($row, $col, $skip);
                if (!CheckData($col, $value, $data)) {
                    setUTF8Data(false);
                    trace("Data corruption on row $row column $col\n");
                    die("Data corruption on row $row column $col\n");
                }
            }
            TraceData($type, "".strlen($value)." bytes");
        }
    }
}


function CheckData($col, $actual, $expected)
{
    $success = true;

    if (IsBinary($col)) {
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


//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    if (! isWindows()) {
        setUTF8Data(true);
    }

    try {
        StreamRead(20, 1);
    } catch (Exception $e) {
        echo $e->getMessage();
    }

    setUTF8Data(false);
}

repro();

?>
--EXPECT--
Test "Stream - Read" completed successfully.
