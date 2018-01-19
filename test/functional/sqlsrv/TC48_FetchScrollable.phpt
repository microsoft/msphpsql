--TEST--
Fetch Scrollabale Data Test
--DESCRIPTION--
Verifies data retrieval with scrollable result sets.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php 
// locale must be set before 1st connection
setUSAnsiLocale();
require('skipif_versions_old.inc'); 
?>
--FILE--
<?php
require_once('MsCommon.inc');

function fetchRow($noRows)
{
    setup();
    $tableName = 'TC48test';
    if (useUTF8Data()) {
        $conn1 = AE\connect(array('CharacterSet'=>'UTF-8'));
    } else {
        $conn1 = AE\connect();
    }
    AE\createTestTable($conn1, $tableName);

    $noRowsInserted = AE\insertTestRows($conn1, $tableName, $noRows);

    $actual = null;
    $expected = null;

    // fetch array (to retrieve reference values)
    $stmt1 = AE\selectFromTable($conn1, $tableName);
    $numFields = sqlsrv_num_fields($stmt1);
    $expected = fetchArray($stmt1, $noRowsInserted, $numFields);
    sqlsrv_free_stmt($stmt1);
    
    $query = "SELECT * FROM [$tableName]";

    // fetch object - FORWARD cursor
    $options = array('Scrollable' => SQLSRV_CURSOR_FORWARD);
    $stmt2 = AE\executeQueryEx($conn1, $query, $options);
    $actual = fetchObject($stmt2, $noRowsInserted, $numFields, SQLSRV_SCROLL_NEXT);
    sqlsrv_free_stmt($stmt2);
    checkData($noRowsInserted, $numFields, $actual, $expected);

    // fetch object - STATIC cursor
    $options = array('Scrollable' => SQLSRV_CURSOR_STATIC);
    $stmt2 = AE\executeQueryEx($conn1, $query, $options);
    $actual = fetchObject($stmt2, $noRowsInserted, $numFields, SQLSRV_SCROLL_RELATIVE);
    sqlsrv_free_stmt($stmt2);
    checkData($noRowsInserted, $numFields, $actual, $expected);

    // fetch object - DYNAMIC cursor
    $options = array('Scrollable' => SQLSRV_CURSOR_DYNAMIC);
    $stmt2 = AE\executeQueryEx($conn1, $query, $options);
    $actual = fetchObject($stmt2, $noRowsInserted, $numFields, SQLSRV_SCROLL_ABSOLUTE);
    sqlsrv_free_stmt($stmt2);
    checkData($noRowsInserted, $numFields, $actual, $expected);

    // fetch object - KEYSET cursor
    $options = array('Scrollable' => SQLSRV_CURSOR_KEYSET);
    $stmt2 = AE\executeQueryEx($conn1, $query, $options);
    $actual = fetchObject($stmt2, $noRowsInserted, $numFields, SQLSRV_SCROLL_PRIOR, 0);
    sqlsrv_free_stmt($stmt2);
    checkData($noRowsInserted, $numFields, $actual, $expected);
    
    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
}

function fetchArray($stmt, $rows, $fields)
{
    $values = array();
    for ($i = 0; $i < $rows; $i++) {
        $row = sqlsrv_fetch_array($stmt);
        if ($row === false) {
            fatalError("Row $i is missing");
        }
        $values[$i] = $row;
    }
    return ($values);
}

function fetchObject($stmt, $rows, $fields, $dir)
{
    trace("\tRetrieving $rows objects with $fields fields each ...\n");
    $values = array();
    for ($i = 0; $i < $rows; $i++) {
        if ($dir == SQLSRV_SCROLL_NEXT) {
            $obj = sqlsrv_fetch_object($stmt, null, null, $dir);
        } elseif ($dir == SQLSRV_SCROLL_PRIOR) {
            if ($i == 0) {
                $obj = sqlsrv_fetch_object($stmt, null, null, SQLSRV_SCROLL_LAST);
            } else {
                $obj = sqlsrv_fetch_object($stmt, null, null, $dir);
            }
        } elseif ($dir == SQLSRV_SCROLL_ABSOLUTE) {
            $obj = sqlsrv_fetch_object($stmt, null, null, $dir, $i);
        } elseif ($dir == SQLSRV_SCROLL_RELATIVE) {
            $obj = sqlsrv_fetch_object($stmt, null, null, $dir, 1);
        }
        if ($obj === false) {
            fatalError("Row $i is missing");
        }
        if ($dir == SQLSRV_SCROLL_PRIOR) {
            $values[$rows - $i - 1] = $obj;
        } else {
            $values[$i] = $obj;
        }
    }
    return ($values);
}

function checkData($rows, $fields, $actualValues, $expectedValues)
{
    if (($actualValues != null) && ($expectedValues != null)) {
        for ($i = 0; $i < $rows; $i++) {
            for ($j = 0; $j < $fields; $j++) {
                $colName = getColName($j + 1);
                $actual = $actualValues[$i]->$colName;
                $expected = $expectedValues[$i][$colName];
                if ($actual != $expected) {
                    die("Data corruption on row ".($i + 1)." column ".($j + 1).": $expected => $actual");
                }
            }
        }
    }
}

// locale must be set before 1st connection
setUSAnsiLocale();
$testName = "Fetch - Scrollable";

// test ansi only if windows or non-UTF8 locales are supported (ODBC 17 and above)
startTest($testName);
if (isLocaleSupported()) {
    try {
        setUTF8Data(false);
        fetchRow(10);
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
    fetchRow(10);
} catch (Exception $e) {
    echo $e->getMessage();
}
endTest($testName);

?>
--EXPECT--
Test "Fetch - Scrollable" completed successfully.
Test "Fetch - Scrollable" completed successfully.
