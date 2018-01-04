--TEST--
Fetch Next Result Test
--DESCRIPTION--
Verifies the functionality of "sqlsrv_next_result"
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
    if (!IsMarsSupported()) {
        return;
    }

    setup();
    $tableName = 'TC46test';
    if (useUTF8Data()) {
        $conn1 = AE\connect(array('CharacterSet'=>'UTF-8'));
    } else {
        $conn1 = AE\connect();
    }
    AE\createTestTable($conn1, $tableName);

    $noRows = 10;
    AE\insertTestRows($conn1, $tableName, $noRows);

    $stmt1 = AE\executeQuery($conn1, "SELECT * FROM [$tableName]");
    $stmt2 = AE\executeQuery($conn1, "SELECT * FROM [$tableName]; SELECT * FROM [$tableName]");
    if (sqlsrv_next_result($stmt2) === false) {
        fatalError("Failed to retrieve next result set");
    }

    $numFields1 = sqlsrv_num_fields($stmt1);
    $numFields2 = sqlsrv_num_fields($stmt2);
    if ($numFields1 != $numFields2) {
        setUTF8Data(false);
        die("Unexpected number of fields: $numField1 => $numFields2");
    }

    trace("Retrieving $noRows rows with $numFields1 fields each ...");
    for ($i = 0; $i < $noRows; $i++) {
        $row1 = sqlsrv_fetch($stmt1);
        $row2 = sqlsrv_fetch($stmt2);
        if (($row1 === false) || ($row2 === false)) {
            fatalError("Row $i is missing");
        }
        for ($j = 0; $j < $numFields1; $j++) {
            if (useUTF8Data()) {
                $fld1 = sqlsrv_get_field($stmt1, $j, SQLSRV_PHPTYPE_STRING('UTF-8'));
                $fld2 = sqlsrv_get_field($stmt2, $j, SQLSRV_PHPTYPE_STRING('UTF-8'));
            } else {
                $fld1 = sqlsrv_get_field($stmt1, $j, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
                $fld2 = sqlsrv_get_field($stmt2, $j, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            }
            if (($fld1 === false) || ($fld2 === false)) {
                fatalError("Field $j of Row $i is missing");
            }
            if ($fld1 != $fld2) {
                setUTF8Data(false);
                die("Data corruption on row ".($i + 1)." column ".($j + 1)." $fld1 => $fld2");
            }
        }
    }
    if (sqlsrv_next_result($stmt1) ||
        sqlsrv_next_result($stmt2)) {
        setUTF8Data(false);
        fatalError("No more results were expected", true);
    }
    sqlsrv_free_stmt($stmt1);
    sqlsrv_free_stmt($stmt2);
    trace(" completed successfully.\n");

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
}

// locale must be set before 1st connection
setUSAnsiLocale();
$testName = "Fetch - Next Result";

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
Test "Fetch - Next Result" completed successfully.
Test "Fetch - Next Result" completed successfully.
