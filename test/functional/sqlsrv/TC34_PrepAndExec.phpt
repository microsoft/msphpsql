--TEST--
Prepare and Execute Test
--DESCRIPTION--
Checks the data returned by a query first prepared and then executed multiple times.
Validates that a prepared statement can be successfully executed more than once.
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

function prepareAndExecute($noPasses)
{
    setup();
    if (useUTF8Data()) {
        $conn1 = AE\connect(array('CharacterSet'=>'UTF-8'));
    } else {
        $conn1 = AE\connect();
    }

    $tableName = 'TC34test';
    AE\createTestTable($conn1, $tableName);
    AE\insertTestRows($conn1, $tableName, 1);

    $values = array();
    $fieldlVal = "";

    // Prepare reference values
    trace("Execute a direct SELECT query on $tableName ...");
    $stmt1 = AE\selectFromTable($conn1, $tableName);
    $numFields1 = sqlsrv_num_fields($stmt1);
    sqlsrv_fetch($stmt1);
    for ($i = 0; $i < $numFields1; $i++) {
        if (useUTF8Data()) {
            $fieldVal = sqlsrv_get_field($stmt1, $i, SQLSRV_PHPTYPE_STRING('UTF-8'));
        } else {
            $fieldVal = sqlsrv_get_field($stmt1, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        }
        if ($fieldVal === false) {
            fatalError("Failed to retrieve field $i", true);
        }
        $values[$i] = $fieldVal;
    }
    sqlsrv_free_stmt($stmt1);
    trace(" $numFields1 fields retrieved.\n");

    // Prepare once and execute several times
    trace("Prepare a SELECT query on $tableName ...");
    $stmt2 = prepareQuery($conn1, "SELECT * FROM [$tableName]");
    $numFields2 = sqlsrv_num_fields($stmt2);
    trace(" $numFields2 fields expected.\n");
    if ($numFields2 != $numFields1) {
        setUTF8Data(false);
        die("Incorrect number of fields: $numFields2");
    }

    for ($j = 0; $j < $noPasses; $j++) {
        trace("Executing the prepared query ...");
        sqlsrv_execute($stmt2);
        sqlsrv_fetch($stmt2);
        for ($i = 0; $i < $numFields2; $i++) {
            if (useUTF8Data()) {
                $fieldVal = sqlsrv_get_field($stmt2, $i, SQLSRV_PHPTYPE_STRING('UTF-8'));
            } else {
                $fieldVal = sqlsrv_get_field($stmt2, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            }
            if ($fieldVal === false) {
                fatalError("Failed to retrieve field $i");
            }
            if ($values[$i] != $fieldVal) {
                setUTF8Data(false);
                die("Incorrect value for field $i at iteration $j");
            }
        }
        trace(" $numFields2 fields verified.\n");
    }
    sqlsrv_free_stmt($stmt2);

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);
}

// locale must be set before 1st connection
setUSAnsiLocale();
$testName = "Statement - Prepare and Execute";

// test ansi only if windows or non-UTF8 locales are supported (ODBC 17 and above)
startTest($testName);
if (isLocaleSupported()) {
    try {
        setUTF8Data(false);
        prepareAndExecute(5);
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
    prepareAndExecute(5);
} catch (Exception $e) {
    echo $e->getMessage();
}
endTest($testName);

?>
--EXPECT--
Test "Statement - Prepare and Execute" completed successfully.
Test "Statement - Prepare and Execute" completed successfully.
