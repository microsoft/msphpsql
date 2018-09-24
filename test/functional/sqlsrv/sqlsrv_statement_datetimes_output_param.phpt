--TEST--
Test retrieving datetime values as output params with statement option ReturnDatesAsStrings
--DESCRIPTION--
Test retrieving datetime values as output params with statement option ReturnDatesAsStrings
with sqlsrv_prepare. When ReturnDatesAsStrings option is false, expect an error to return.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function runTest($conn, $storedProcName, $inputValue, $sqlType, $dateAsString)
{
    $outDateStr = '';
    $outSql = AE\getCallProcSqlPlaceholders($storedProcName, 1);
    $stmt = sqlsrv_prepare($conn, $outSql,
                            array(array(&$outDateStr, SQLSRV_PARAM_OUT, null, $sqlType)), array('ReturnDatesAsStrings' => $dateAsString));
    if (!$stmt) {
        fatalError("Failed when preparing to call $storedProcName");
    }
    $result = sqlsrv_execute($stmt);
    if ($dateAsString) {
        // Expect to succeed when returning a DateTime value as a string
        // The output param value should be the same as the input value
        if (!$result) {
            fatalError("Failed when invoking $storedProcName");
        }
        if ($outDateStr != $inputValue) {
            echo "Expected $inputValue but got $outDateStr\n";
        }
    } else {
        // Expect to fail with an error message because setting a DateTime object as the
        // output parameter is not allowed
        if ($result) {
            fatalError("Returning DateTime as output param is expected to fail!");
        }
        // Check if the error message is the expected one
        $error = sqlsrv_errors()[0]['message'];
        $message = 'An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters';
        if (strpos($error, $message) === false) {
            print_r(sqlsrv_errors());
        }
    }
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);
date_default_timezone_set('America/Los_Angeles');

// Connect with ReturnDatesAsStrings option set to true
$conn = connect(array('ReturnDatesAsStrings' => true));
if (!$conn) {
    fatalError("Could not connect.\n");
}

// Generate input values for the test table
$query = 'SELECT CONVERT(date, SYSDATETIME()), SYSDATETIME(), SYSDATETIMEOFFSET(), CONVERT(time, CURRENT_TIMESTAMP)';
$stmt = sqlsrv_query($conn, $query);
$values = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// Connect again with ColumnEncryption data
$conn = AE\connect(array('ReturnDatesAsStrings' => true));
if (!$conn) {
    fatalError("Could not connect.\n");
}

// Create the test table of date and time columns
$tableName = 'OuputParamDateAsString';
$columns = array('c1', 'c2', 'c3', 'c4');
$dataTypes = array('date', 'datetime2', 'datetimeoffset', 'time');
$sqlTypes = array(SQLSRV_SQLTYPE_DATE,
                  SQLSRV_SQLTYPE_DATETIME2,
                  SQLSRV_SQLTYPE_DATETIMEOFFSET,
                  SQLSRV_SQLTYPE_TIME);
$colMeta = array(new AE\ColumnMeta($dataTypes[0], $columns[0]),
                 new AE\ColumnMeta($dataTypes[1], $columns[1]),
                 new AE\ColumnMeta($dataTypes[2], $columns[2]),
                 new AE\ColumnMeta($dataTypes[3], $columns[3]));
AE\createTable($conn, $tableName, $colMeta);

// Insert data values
$inputData = array($colMeta[0]->colName => $values[0],
                   $colMeta[1]->colName => $values[1],
                   $colMeta[2]->colName => $values[2],
                   $colMeta[3]->colName => $values[3]);
$stmt = AE\insertRow($conn, $tableName, $inputData);
if (!$stmt) {
    fatalError("Failed to insert data.\n");
}
sqlsrv_free_stmt($stmt);

for ($i = 0; $i < count($columns); $i++) {
    // create the stored procedure first
    $storedProcName = "spDateTimeOutParam" . $i;
    $procArgs = "@col $dataTypes[$i] OUTPUT";
    $procCode = "SELECT @col = $columns[$i] FROM $tableName";
    createProc($conn, $storedProcName, $procArgs, $procCode);

    // call stored procedure to retrieve output param
    runTest($conn, $storedProcName, $values[$i], $sqlTypes[$i], true);
    runTest($conn, $storedProcName, $values[$i], $sqlTypes[$i], false);
}

dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done
