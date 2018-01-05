--TEST--
Fetch Object Test
--DESCRIPTION--
Verifies data retrieval via "sqlsrv_fetch_object".
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

class TestClass
{
    public function __construct($a1, $a2, $a3)
    {
    }
}

function fetchRow($minFetchMode, $maxFetchMode)
{
    setup();
    $tableName = 'TC45test';
    if (useUTF8Data()) {
        $conn1 = AE\connect(array('CharacterSet'=>'UTF-8'));
    } else {
        $conn1 = AE\connect();
    }
    AE\createTestTable($conn1, $tableName);

    $noRows = 10;
    $noRowsInserted = AE\insertTestRows($conn1, $tableName, $noRows);

    $actual = null;
    $expected = null;
    $numFields = 0;
    for ($k = $minFetchMode; $k <= $maxFetchMode; $k++) {
        $stmt1 = AE\selectFromTable($conn1, $tableName);
        if ($numFields == 0) {
            $numFields = sqlsrv_num_fields($stmt1);
        } else {
            $count = sqlsrv_num_fields($stmt1);
            if ($count != $numFields) {
                die("Unexpected number of fields: $count");
            }
        }

        switch ($k) {
        case 0:        // fetch array (to retrieve reference values)
            $expected = fetchArray($stmt1, $noRowsInserted, $numFields);
            break;

        case 1:        // fetch object (without class)
            $actual = fetchObject($stmt1, $noRowsInserted, $numFields, false);
            checkData($noRowsInserted, $numFields, $actual, $expected);
            break;

        case 2:        // fetch object (with class)
            $actual = fetchObject($stmt1, $noRowsInserted, $numFields, true);
            checkData($noRowsInserted, $numFields, $actual, $expected);
            break;

        default:    // default
            break;
        }
        sqlsrv_free_stmt($stmt1);
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
}


function fetchObject($stmt, $rows, $fields, $useClass)
{
    trace("\tRetrieving $rows objects with $fields fields each ...\n");
    $values = array();
    for ($i = 0; $i < $rows; $i++) {
        if ($useClass) {
            $obj = sqlsrv_fetch_object($stmt, "TestClass", array(1, 2, 3));
        } else {
            $obj = sqlsrv_fetch_object($stmt);
        }
        if ($obj === false) {
            fatalError("In fetchObject: Row $i is missing");
        }
        $values[$i] = $obj;
    }
    return ($values);
}


function fetchArray($stmt, $rows, $fields)
{
    $values = array();
    for ($i = 0; $i < $rows; $i++) {
        $row = sqlsrv_fetch_array($stmt);
        if ($row === false) {
            fatalError("In fetchArray: Row $i is missing");
        }
        $values[$i] = $row;
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
$testName = "Fetch - Object";

// test ansi only if windows or non-UTF8 locales are supported (ODBC 17 and above)
startTest($testName);
if (isLocaleSupported()) {
    try {
        setUTF8Data(false);
        fetchRow(0, 2);
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
    fetchRow(0, 2);
} catch (Exception $e) {
    echo $e->getMessage();
}
endTest($testName);

?>
--EXPECT--
Test "Fetch - Object" completed successfully.
Test "Fetch - Object" completed successfully.
