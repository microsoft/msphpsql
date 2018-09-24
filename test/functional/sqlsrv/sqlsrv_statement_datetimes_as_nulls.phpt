--TEST--
Test retrieving null datetime values with statement option ReturnDatesAsStrings as true
--DESCRIPTION--
Test retrieving null datetime values with statement option ReturnDatesAsStrings as true,
which is false by default. Whether retrieved as strings or date time objects should return
NULLs.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function testFetch($conn, $query, $columns, $withBuffer = false)
{
    // The statement option ReturnDatesAsStrings set to true
    // Test different fetching
    if ($withBuffer){
        $options = array('Scrollable' => 'buffered', 'ReturnDatesAsStrings' => true);
    } else {
        $options = array('ReturnDatesAsStrings' => true);
    }

    $size = count($columns);
    $stmt = sqlsrv_prepare($conn, $query, array(), $options);
    // Fetch by getting one field at a time
    sqlsrv_execute($stmt);
    if( sqlsrv_fetch( $stmt ) === false) {
        fatalError("Failed in retrieving data\n");
    }
    for ($i = 0; $i < $size; $i++) {
        $field = sqlsrv_get_field($stmt, $i);   // expect string
        if (!is_null($field)) {
            echo "Expected null for column $columns[$i] but got: ";
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

        if (!is_null($val)) {
            echo "Expected null for column $columns[$i] but got: ";
            var_dump($val);
        }
    }
}

function createTestTable($conn, $tableName, $columns)
{
    // Create the test table of date and time columns
    $dataTypes = array('date', 'smalldatetime', 'datetime', 'datetime2', 'datetimeoffset', 'time');

    $colMeta = array(new AE\ColumnMeta($dataTypes[0], $columns[0]),
                     new AE\ColumnMeta($dataTypes[1], $columns[1]),
                     new AE\ColumnMeta($dataTypes[2], $columns[2]),
                     new AE\ColumnMeta($dataTypes[3], $columns[3]),
                     new AE\ColumnMeta($dataTypes[4], $columns[4]),
                     new AE\ColumnMeta($dataTypes[5], $columns[5]));
    AE\createTable($conn, $tableName, $colMeta);

    // Insert null values
    $inputData = array($colMeta[0]->colName => null,
                       $colMeta[1]->colName => null,
                       $colMeta[2]->colName => null,
                       $colMeta[3]->colName => null,
                       $colMeta[4]->colName => null,
                       $colMeta[5]->colName => null);
    $stmt = AE\insertRow($conn, $tableName, $inputData);
    if (!$stmt) {
        fatalError("Failed to insert data.\n");
    }
    sqlsrv_free_stmt($stmt);
}

function runTest($tableName, $columns, $dateAsString)
{
    // Connect
    $conn = AE\connect(array('ReturnDatesAsStrings' => $dateAsString));
    if (!$conn) {
        fatalError("Could not connect.\n");
    }

    $query = "SELECT * FROM $tableName";
    testFetch($conn, $query, $columns);
    testFetch($conn, $query, $columns, true);

    sqlsrv_close($conn);
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

$tableName = "TestNullDateTime";
$columns = array('c1', 'c2', 'c3', 'c4', 'c5', 'c6');

// Connect
$conn = AE\connect();
if (!$conn) {
    fatalError("Could not connect.\n");
}

createTestTable($conn, $tableName, $columns);

runTest($tableName, $columns, true);
runTest($tableName, $columns, false);

dropTable($conn, $tableName);

sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done
