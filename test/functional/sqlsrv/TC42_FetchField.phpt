--TEST--
Fetch Field Test
--DESCRIPTION--
Verifies the ability to successfully retrieve field data via "sqlsrv_get_field" by
retrieving fields from a table including rows with all supported SQL types (28 types).
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

function fetchFields()
{
    setup();
    $tableName = 'TC42test';
    if (useUTF8Data()) {
        $conn1 = AE\connect(array('CharacterSet'=>'UTF-8'));
    } else {
        $conn1 = AE\connect();
    }

    AE\createTestTable($conn1, $tableName);

    $noRows = 10;
    $noRowsInserted = AE\insertTestRows($conn1, $tableName, $noRows);

    $stmt1 = AE\selectFromTable($conn1, $tableName);
    $numFields = sqlsrv_num_fields($stmt1);

    trace("Retrieving $noRowsInserted rows with $numFields fields each ...");
    for ($i = 0; $i < $noRowsInserted; $i++) {
        $row = sqlsrv_fetch($stmt1);
        if ($row === false) {
            fatalError("Row $i is missing", true);
        }
        for ($j = 0; $j < $numFields; $j++) {
            $fld = sqlsrv_get_field($stmt1, $j);
            $col = $j+1;
            if ($fld === false) {
                fatalError("Field $j of Row $i is missing\n", true);
            }
        }
    }
    sqlsrv_free_stmt($stmt1);
    trace(" completed successfully.\n");

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
}

// locale must be set before 1st connection
setUSAnsiLocale();
$testName = "Fetch - Field";

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
Test "Fetch - Field" completed successfully.
Test "Fetch - Field" completed successfully.
