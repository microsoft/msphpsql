--TEST--
Test a batch query with different cursor types
--DESCRIPTION--
Verifies that batch queries don't work with dynamic, static, and keyset
server-side cursors, and checks that correct column and row counts are
returned otherwise. For information on the expected behaviour of cursors
with batch queries, see
https://docs.microsoft.com/en-us/previous-versions/visualstudio/aa266531(v=vs.60)
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

// All supported cursor types
$cursors = array('forward', 'dynamic', 'static', 'keyset', 'buffered');

// Expected error messages
$noCursor = array(array('01000', 16954, 'Executing SQL directly; no cursor.'),
                  array('01S02', 0, 'Cursor type changed'));
$wrongCursor = array(array('IMSSP', -50, 'This function only works with statements that have static or keyset scrollable cursors.'));
$noNextResult = array(array('IMSSP', -22, 'There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.'));

// Data for testing, all integer types
$data = array(array(86, -217483648, 0, -432987563, 7, 217483647),
              array(0, 31, 127, 255, 1, 10),
              array(4534, -212, 32767, 0, 7, -32768),
              array(-1, 546098342985600, 9223372000000000000, 5115115115115, 7, -7),
              array(0, 1, 0, 0, 1, 1),
             );

// Column names
$colName = array('c1_int', 'c2_tinyint', 'c3_smallint', 'c4_bigint', 'c5_bit');

// Fetch one column at a time
$expectedCols = 1;

// Number of table rows
$expectedRows = sizeof($data[0]);

// Expected result sets = number of columns, since the batch fetches each column sequentially
$expectedResultSets = sizeof($colName);

function checkErrors($expectedError)
{
    $actualError = sqlsrv_errors();
    
    // Make sure we have errors to check for
    if (is_null($actualError)) {
        fatalError("Expected error message, got none\n");
    }
    
    // Make sure we have the same number of error/info messages
    if (sizeof($actualError) != sizeof($expectedError)) {
        fatalError("Got wrong number of errors\n");
    }
    
    // Make sure the SQLSTATE, code, and message are identical
    $e = 0;
    foreach ($actualError AS $error) {
        if ($error[0] != $expectedError[$e][0] or $error[1] != $expectedError[$e][1] or !fnmatch('*'.$expectedError[$e][2], $error[2])) {
            fatalError("Wrong error message, expected ".$expectedError[$e][2].", got $error[2]\n");
        }
        ++$e;
    }
}

function checkColumnsAndRows($stmt, $cursor, $error)
{
    global $expectedCols, $expectedRows;
    
    $cols = sqlsrv_num_fields($stmt);
    
    if ($cols != $expectedCols) {
        fatalError("Incorrect number of columns returned with $cursor cursor. Expected $expectedCols columns, got $cols columns\n");
    }

    $rows = sqlsrv_num_rows($stmt);

    if ($cursor == 'buffered') {
        if ($rows != $expectedRows) {
            fatalError("Incorrect number of columns returned with buffered cursor. Expected $expectedRows rows, got $rows rows\n");
        }
    } else {
        if ($rows !== false) {
            fatalError("Expected sqlsrv_num_rows to return false with $cursor cursor, instead returned $rows rows\n");
        } else {
            checkErrors($error);
        }
    }
}

sqlsrv_configure('WarningsReturnAsErrors', 1);

$conn = AE\connect();

// Create and populate a table of integer types
$tableName = 'batch_query_test';
$columns = array(new AE\ColumnMeta('int', $colName[0]),
                 new AE\ColumnMeta('tinyint', $colName[1]),
                 new AE\ColumnMeta('smallint',$colName[2]),
                 new AE\ColumnMeta('bigint', $colName[3]),
                 new AE\ColumnMeta('bit', $colName[4]));
                 
$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}
sqlsrv_free_stmt($stmt);

$inputs = array();

// Insert each row. Need an associative array to use insertRow()
for ($i = 0; $i < $expectedRows; ++$i) {
    $inputs = array();
    for ($j = 0; $j < $expectedResultSets; ++$j) {
        $inputs[$colName[$j]] = $data[$j][$i];
    }

    $stmt = AE\insertRow($conn, $tableName, $inputs);
    sqlsrv_free_stmt($stmt);
}

$query = "SELECT c1_int FROM $tableName;
          SELECT c2_tinyint FROM $tableName;
          SELECT c3_smallint FROM $tableName;
          SELECT c4_bigint FROM $tableName;
          SELECT c5_bit FROM $tableName;";

// Test the batch query with different cursor types
for ($i = 0; $i < sizeof($cursors); ++$i) {
    $cursor = $cursors[$i];
    echo "Testing with $cursor cursor...\n";
    
    $stmt = sqlsrv_prepare($conn, $query, array(), array("Scrollable"=>$cursor));
    if (!$stmt) { 
        fatalError("Error preparing statement with $cursor cursor\n"); 
    }

    if (!sqlsrv_execute($stmt)) {
        if ($cursor == 'forward' or $cursor == 'buffered') {
            fatalError("Statement execution failed unexpectedly with a $cursor cursor\n");
        } else {
            checkErrors($noCursor);
            continue;
        }
    }

    $numResultSets = 0;

    // Check the column and row count before and after running through
    // each result set, because some cursor types may return the number
    // of rows only after fetching all rows in the result set
    do {
        checkColumnsAndRows($stmt, $cursor, $wrongCursor);
        
        $row = 0;
        while ($res = sqlsrv_fetch_array($stmt)) {
            if ($res[0] != $data[$numResultSets][$row]) {
                fatalError("Wrong result, expected ".$data[$numResultSets][$row].", got $res[0]\n");
            }
            ++$row;
        }

        checkColumnsAndRows($stmt, $cursor, $wrongCursor);
        ++$numResultSets;

    } while ($next = sqlsrv_next_result($stmt));
    
    if ($numResultSets != $expectedResultSets) {
        fatalError("Unexpected number of result sets, expected $expectedResultedSets, got $numResultSets\n");
    }

    // We expect an error if sqlsrv_next_result returns false,
    // but not if it returns null (i.e. if we are genuinely at
    // the end of all the result sets with a buffered cursor)
    if ($next === false) {
        if ($cursor == 'forward') {
            checkErrors($noNextResult);
        } else {
            fatalError("sqlsrv_next_result failed with a $cursor cursor\n");
        }
    }
    
    sqlsrv_free_stmt($stmt);
}

dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Done.\n";
?>
--EXPECT--
Testing with forward cursor...
Testing with dynamic cursor...
Testing with static cursor...
Testing with keyset cursor...
Testing with buffered cursor...
Done.
