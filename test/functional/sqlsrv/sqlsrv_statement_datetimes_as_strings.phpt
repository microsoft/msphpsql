--TEST--
Test retrieving datetime values with statement option ReturnDatesAsStrings set to true
--DESCRIPTION--
Test retrieving datetime values with statement option ReturnDatesAsStrings set to true,
which is false by default. The statement option should override the corresponding
connection option ReturnDatesAsStrings.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function compareDateTime($expectedStr, $actualObj)
{
    $dtime = date_create($expectedStr);
    $dtExpected = $dtime->format('Y-m-d H:i:s.u');

    // actual datetime value from date time object to string
    $dtActual = date_format($actualObj, 'Y-m-d H:i:s.u');

    return ($dtActual === $dtExpected);
}

function testNoOption($conn, $tableName, $inputs, $exec)
{
    // Without the statement option, should return datetime values as strings
    // because the connection option ReturnDatesAsStrings is set to true
    $query = "SELECT * FROM $tableName";
    if ($exec) {
        $stmt = sqlsrv_query($conn, $query);
    } else {
        $stmt = sqlsrv_prepare($conn, $query);
        sqlsrv_execute($stmt);
    }

    $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

    // Compare values only
    $diffs = array_diff($inputs, $results);
    if (!empty($diffs)) {
        echo 'The results are different from the input values: ';
        print_r($diffs);
    }
}

function testStmtOption($conn, $tableName, $inputs, $stmtDateAsStr)
{
    // The statement option should always override the connection option
    $query = "SELECT * FROM $tableName";
    $options = array('ReturnDatesAsStrings' => $stmtDateAsStr);
    $stmt = sqlsrv_query($conn, $query, array(), $options);

    if ($stmtDateAsStr) {
        $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);

        // Compare values only
        $diffs = array_diff($inputs, $results);
        if (!empty($diffs)) {
            echo 'The results are different from the input values: ';
            print_r($diffs);
        }
    } else {
        $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

        // Expect DateTime Objects in $results
        for ($i = 0; $i < count($inputs); $i++) {
            if (is_object($results[$i])) {
                $matched = compareDateTime($inputs[$i], $results[$i]);
                if (!$matched) {
                    echo "Expected a DateTime object of $inputs[$i] but got: \n";
                    var_dump($results[$i]);
                }
            } else {
                echo "Expect a DateTime object but got $results[$i]\n";
            }
        }
    }
}

function testFetching($conn, $tableName, $inputs, $columns, $withBuffer)
{
    // The statement option ReturnDatesAsStrings set to true
    // Test different fetching
    $query = "SELECT * FROM $tableName";
    if ($withBuffer){
        $options = array('Scrollable' => 'buffered', 'ReturnDatesAsStrings' => true);
    } else {
        $options = array('ReturnDatesAsStrings' => true);
    }

    $size = count($inputs);
    $stmt = sqlsrv_prepare($conn, $query, array(), $options);

    // Fetch by getting one field at a time
    sqlsrv_execute($stmt);

    if( sqlsrv_fetch( $stmt ) === false) {
        fatalError("Failed in retrieving data\n");
    }
    for ($i = 0; $i < $size; $i++) {
        $field = sqlsrv_get_field($stmt, $i);   // expect string
        if ($field != $inputs[$i]) {
            echo "Expected $inputs[$i] for column $columns[$i] but got: ";
            var_dump($field);
        }
    }

    // Fetch row as an object
    sqlsrv_execute($stmt);
    $object = sqlsrv_fetch_object($stmt);

    $objArray = (array)$object;    // turn the object into an associated array
    for ($i = 0; $i < $size; $i++) {
        $col = $columns[$i];
        $val = $objArray[$col];

        if ($val != $inputs[$i]) {
            echo "Expected $inputs[$i] for column $columns[$i] but got: ";
            var_dump($val);
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
$query = 'SELECT CONVERT(date, SYSDATETIME()), SYSDATETIME(), 
                 CONVERT(smalldatetime, SYSDATETIME()),
                 CONVERT(datetime, SYSDATETIME()), 
                 SYSDATETIMEOFFSET(), 
                 CONVERT(time, SYSDATETIME())';
$stmt = sqlsrv_query($conn, $query);
$values = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

// Connect again with ColumnEncryption data
$conn = AE\connect(array('ReturnDatesAsStrings' => true));

// Create the test table of date and time columns
$tableName = 'StmtDateAsString';
$columns = array('c1', 'c2', 'c3', 'c4', 'c5', 'c6');
$dataTypes = array('date', 'datetime2', 'smalldatetime', 'datetime', 'datetimeoffset', 'time');

$colMeta = array(new AE\ColumnMeta($dataTypes[0], $columns[0]),
                 new AE\ColumnMeta($dataTypes[1], $columns[1]),
                 new AE\ColumnMeta($dataTypes[2], $columns[2]),
                 new AE\ColumnMeta($dataTypes[3], $columns[3]),
                 new AE\ColumnMeta($dataTypes[4], $columns[4]),
                 new AE\ColumnMeta($dataTypes[5], $columns[5]));
AE\createTable($conn, $tableName, $colMeta);

// Insert data values
$inputData = array($colMeta[0]->colName => $values[0],
                   $colMeta[1]->colName => $values[1],
                   $colMeta[2]->colName => $values[2],
                   $colMeta[3]->colName => $values[3],
                   $colMeta[4]->colName => $values[4],
                   $colMeta[5]->colName => $values[5]);
$stmt = AE\insertRow($conn, $tableName, $inputData);
if (!$stmt) {
    fatalError("Failed to insert data.\n");
}
sqlsrv_free_stmt($stmt);

// Do not set ReturnDatesAsStrings at statement level
testNoOption($conn, $tableName, $values, true);
testNoOption($conn, $tableName, $values, false);

// Set ReturnDatesAsStrings to false at statement level
testStmtOption($conn, $tableName, $values, false);

sqlsrv_close($conn);

// Now connect but with ReturnDatesAsStrings option set to false
$conn = AE\connect(array('ReturnDatesAsStrings' => false));
if (!$conn) {
    fatalError("Could not connect.\n");
}

// Set ReturnDatesAsStrings to true at statement level
testStmtOption($conn, $tableName, $values, true);

// Test fetching by setting ReturnDatesAsStrings to true at statement level
testFetching($conn, $tableName, $values, $columns, true);
testFetching($conn, $tableName, $values, $columns, false);

dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done
