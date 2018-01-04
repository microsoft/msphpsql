--TEST--
Fetch Array Test
--DESCRIPTION--
Verifies data retrieval via "sqlsrv_fetch_array",
by checking all fetch type modes.
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

function fetchRow($minFetchMode, $maxFetchMode)
{
    if (!isMarsSupported()) {
        return;
    }

    setup();
    $tableName = 'TC44test';
    if (useUTF8Data()) {
        $conn1 = AE\connect(array( 'CharacterSet'=>'UTF-8' ));
    } else {
        $conn1 = AE\connect();
    }
    $tableName = 'TC44Test';
    AE\createTestTable($conn1, $tableName);

    $noRows = 10;
    $numFields = 0;
    AE\insertTestRows($conn1, $tableName, $noRows);

    for ($k = $minFetchMode; $k <= $maxFetchMode; $k++) {
        $stmt1 = AE\selectFromTable($conn1, $tableName);
        $stmt2 = AE\selectFromTable($conn1, $tableName);
        if ($numFields == 0) {
            $numFields = sqlsrv_num_fields($stmt1);
        } else {
            $count = sqlsrv_num_fields($stmt1);
            if ($count != $numFields) {
                die("Unexpected number of fields: $count");
            }
        }

        switch ($k) {
        case 1:        // fetch array - numeric mode
            fetchArray($stmt1, $stmt2, SQLSRV_FETCH_NUMERIC, $noRows, $numFields);
            break;

        case 2:        // fetch array - associative mode
            fetchArray($stmt1, $stmt2, SQLSRV_FETCH_ASSOC, $noRows, $numFields);
            break;

        case 3:        // fetch array - both numeric & associative
            fetchArray($stmt1, $stmt2, SQLSRV_FETCH_BOTH, $noRows, $numFields);
            break;

        default:    // default
            fetchArray($stmt1, $stmt2, null, $noRows, $numFields);
            break;
        }

        sqlsrv_free_stmt($stmt1);
        sqlsrv_free_stmt($stmt2);
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
}

function fetchArray($stmt, $stmtRef, $mode, $rows, $fields)
{
    $size = $fields;
    $fetchMode = $mode;
    if ($fetchMode == SQLSRV_FETCH_NUMERIC) {
        trace("\tRetrieving $rows arrays of size $size (Fetch Mode = NUMERIC) ...\n");
    } elseif ($fetchMode == SQLSRV_FETCH_ASSOC) {
        trace("\tRetrieving $rows arrays of size $size (Fetch Mode = ASSOCIATIVE) ...\n");
    } elseif ($fetchMode == SQLSRV_FETCH_BOTH) {
        $size = $fields * 2;
        trace("\tRetrieving $rows arrays of size $size (Fetch Mode = BOTH) ...\n");
    } else {
        $fetchMode = null;
        $size = $fields * 2;
        trace("\tRetrieving $rows arrays of size $size (Fetch Mode = DEFAULT) ...\n");
    }
    for ($i = 0; $i < $rows; $i++) {
        if ($fetchMode == null) {
            $row = sqlsrv_fetch_array($stmt);
        } else {
            $row = sqlsrv_fetch_array($stmt, $fetchMode);
        }
        if ($row === false) {
            fatalError("Row $i is missing");
        }
        $rowSize = count($row);
        if ($rowSize != $size) {
            die("Row array has an incorrect size: ".$rowSize);
        }
        $rowRref = sqlsrv_fetch($stmtRef);
        for ($j = 0; $j < $fields; $j++) {
            if (!checkData($row, $stmtRef, $j, $fetchMode)) {
                die("Data corruption on row ".($i + 1)." column ".($j + 1));
            }
        }
    }
}


function checkData($row, $stmt, $index, $mode)
{
    $success = true;

    $col = $index + 1;
    $actual = (($mode == SQLSRV_FETCH_ASSOC) ? $row[getColName($col)] : $row[$index]);
    $expected = null;

    if (!isUpdatable($col)) {
        // do not check the timestamp
    } elseif (isNumeric($col) || isDateTime($col)) {
        $expected = sqlsrv_get_field($stmt, $index);
        if ($expected != $actual) {
            $success = false;
        }
    } elseif (isBinary($col)) {
        $expected = sqlsrv_get_field($stmt, $index, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        $actual = bin2hex($actual);
        if (strcasecmp($actual, $expected) != 0) {
            $success = false;
        }
    } else { // if (isChar($col))
        if (useUTF8Data()) {
            $expected = sqlsrv_get_field($stmt, $index, SQLSRV_PHPTYPE_STRING('UTF-8'));
        } else {
            $expected = sqlsrv_get_field($stmt, $index, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        }
        if (strcmp($actual, $expected) != 0) {
            $success = false;
        }
    }
    if (!$success) {
        trace("\nData error\nExpected:\n$expected\nActual:\n$actual\n");
    }
    return ($success);
}

// locale must be set before 1st connection
setUSAnsiLocale();
$testName = "Fetch - Array";

// test ansi only if windows or non-UTF8 locales are supported (ODBC 17 and above)
startTest($testName);
if (isLocaleSupported()) {
    try {
        setUTF8Data(false);
        fetchRow(1, 4);
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
    fetchRow(1, 4);
} catch (Exception $e) {
    echo $e->getMessage();
}
endTest($testName);

?>
--EXPECT--
Test "Fetch - Array" completed successfully.
Test "Fetch - Array" completed successfully.
