--TEST--
Fetch Field Data Test verifies the data retrieved via sqlsrv_get_field
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?
// locale must be set before 1st connection
setUSAnsiLocale();
require('skipif_versions_old.inc');
?>
--FILE--
<?php

require_once('MsCommon.inc');

function fetchFields()
{
    setup();
    $tableName = 'TC43test';

    if (useUTF8Data()) {
        $conn1 = AE\connect(array('CharacterSet'=>'UTF-8'));
    } else {
        $conn1 = AE\connect();
    }

    AE\createTestTable($conn1, $tableName);

    $startRow = 1;
    $noRows = 20;
    AE\insertTestRowsByRange($conn1, $tableName, $startRow, $startRow + $noRows - 1);

    $query = "SELECT * FROM [$tableName] ORDER BY c27_timestamp";
    $stmt1 = AE\executeQuery($conn1, $query);
    $numFields = sqlsrv_num_fields($stmt1);

    trace("Retrieving $noRows rows with $numFields fields each ...");
    for ($i = 0; $i < $noRows; $i++) {
        $row = sqlsrv_fetch($stmt1);
        if ($row === false) {
            fatalError("Row $i is missing");
        }
        for ($j = 0; $j < $numFields; $j++) {
            if (useUTF8Data()) {
                $fld = sqlsrv_get_field($stmt1, $j, SQLSRV_PHPTYPE_STRING('UTF-8'));
            } else {
                $fld = sqlsrv_get_field($stmt1, $j, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            }
            if ($fld === false) {
                fatalError("Field $j of Row $i is missing");
            }
            $col = $j + 1;
            if (isUpdatable($col)) {
                // should check data even if $fld is null
                $data = AE\getInsertData($startRow + $i, $col);
                if (!checkData($col, $fld, $data)) {
                    echo("\nData error\nExpected:\n$data\nActual:\n$fld\n");

                    setUTF8Data(false);
                    die("Data corruption on row ".($startRow + $i)." column $col");
                }
            }
        }
    }
    sqlsrv_free_stmt($stmt1);
    trace(" completed successfully.\n");

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
}

function checkData($col, $actual, $expected)
{
    $success = true;

    if (isNumeric($col)) {
        if (floatval($actual) != floatval($expected)) {
            $success = false;
        }
    } else {
        // Do not trim data because the data itself can be some space characters
        $len = strlen($expected);
        if (isDateTime($col)) {
            $len = min(strlen("YYYY-MM-DD HH:mm:ss"), $len);
        }
        if (strncasecmp($actual, $expected, $len) != 0) {
            $success = false;
        }
    }
    if (!$success) {
        trace("\nData error\nExpected:\n$expected\nActual:\n$actual\n");
    }

    return ($success);
}

setUSAnsiLocale();
$testName = "Fetch - Field Data";

// test ansi only if windows or non-UTF8 locales are supported (ODBC 17 and above)
startTest($testName);
if (isLocaleSupported()) {

    try {
        setUTF8Data(false);
        fetchFields();
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
    fetchFields();
} catch (Exception $e) {
    echo $e->getMessage();
}
endTest($testName);

?>
--EXPECT--
Test "Fetch - Field Data" completed successfully.
Test "Fetch - Field Data" completed successfully.
