--TEST--
Test a batch query with different cursor types
--DESCRIPTION--
Verifies that batch queries don't work with dynamic, static, and keyset
server-side cursors, and checks that correct column and row counts are
returned otherwise
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$cursors = array('forward', 'dynamic', 'static', 'keyset', 'buffered');

$expectedCols = 1;
$expectedRows = 5;
$expectedResultSets = 5;

//expected errors
$noCursor = array(array('01000', 16954, '[Microsoft][ODBC Driver 17 for SQL Server][SQL Server]Executing SQL directly; no cursor.'),
                  array('01S02', 0, '[Microsoft][ODBC Driver 17 for SQL Server]Cursor type changed'));
$wrongCursor = array(array('IMSSP', -50, 'This function only works with statements that have static or keyset scrollable cursors.'));
$noNextResult = array(array('IMSSP', -22, 'There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.'));

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
        if ($error[0] != $expectedError[$e][0] or $error[1] != $expectedError[$e][1] or $error[2] != $expectedError[$e][2]) {
            fatalError("Wrong error message, expected ".$error[2].", got ".$expectedError[$e][2]."\n");
        }
        ++$e;
    }
}

function checkColumnsAndRows($stmt, $cursor, $error)
{
    global $expectedCols, $expectedRows;
    
    $cols = sqlsrv_num_fields($stmt);
    
    if ($cols != $expectedCols) {
        fatalError("Incorrect number of columns returned with ".$cursor." cursor. Expected ".$expectedCols." columns, got ".$cols." columns\n");
    }

    $rows = sqlsrv_num_rows($stmt);

    if ($cursor == 'buffered') {
        if ($rows != $expectedRows) {
            fatalError("Incorrect number of columns returned with buffered cursor. Expected ".$expectedRows." rows, got ".$rows." rows\n");
        }
    } else {
        if ($rows !== false) {
            fatalError("Expected sqlsrv_num_rows to return false with ".$cursor." cursor, instead returned ".$rows." rows\n");
        } else {
            checkErrors($error);
        }
    }
}

sqlsrv_configure('WarningsReturnAsErrors', 1);

$conn = AE\connect();

// Create and populate a table of integer types
$tableName = 'batch_query_test';
$columns = array(new AE\ColumnMeta('int', 'c1_int'),
                 new AE\ColumnMeta('tinyint', 'c2_tinyint'),
                 new AE\ColumnMeta('smallint', 'c3_smallint'),
                 new AE\ColumnMeta('bigint', 'c4_bigint'),
                 new AE\ColumnMeta('bit', 'c5_bit'));
                 
$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}
sqlsrv_free_stmt($stmt);

$inputs = array(array('c1_int'=>86, 'c2_tinyint'=>0, 'c3_smallint'=>4534, 'c4_bigint'=>-1, 'c5_bit'=>0),
                array('c1_int'=>-217483648, 'c2_tinyint'=>31, 'c3_smallint'=>-212, 'c4_bigint'=>546098342985694, 'c5_bit'=>1),
                array('c1_int'=>0, 'c2_tinyint'=>127, 'c3_smallint'=>32767, 'c4_bigint'=>9223372000000000000, 'c5_bit'=>0),
                array('c1_int'=>-432987563, 'c2_tinyint'=>255, 'c3_smallint'=>0, 'c4_bigint'=>5115115115115115115, 'c5_bit'=>0),
                array('c1_int'=>7, 'c2_tinyint'=>1, 'c3_smallint'=>7, 'c4_bigint'=>7, 'c5_bit'=>1),
               );
               
for ($i=0; $i < sizeof($inputs); ++$i) {
    $stmt = AE\insertRow($conn, $tableName, $inputs[$i]);
    sqlsrv_free_stmt($stmt);
}

$query = "SELECT c1_int from batch_query_test;
          SELECT c2_tinyint from batch_query_test;
          SELECT c3_smallint from batch_query_test;
          SELECT c4_bigint from batch_query_test;
          SELECT c5_bit from batch_query_test;";

// Test the batch query with different cursor types
for ($i = 0; $i < sizeof($cursors); ++$i)
{
    $cursor = $cursors[$i];
    echo "Testing with ".$cursor." cursor...\n";
    
    $stmt = sqlsrv_prepare($conn, $query, array(), array("Scrollable"=>$cursor));
    if (!$stmt) { 
        fatalError("Error preparing statement with ".$cursor." cursor\n"); 
    }

    if (!sqlsrv_execute($stmt)) {
        if ($cursor == 'forward' or $cursor == 'buffered') {
            fatalError("Statement execution failed unexpectedly with a ".$cursor." cursor\n");
        } else {
            checkErrors($noCursor);
            continue;
        }
    }

    // Check the column and row count before and after running through
    // all results
    checkColumnsAndRows($stmt, $cursor, $wrongCursor);

    while ($res = sqlsrv_fetch($stmt))
    { }

    checkColumnsAndRows($stmt, $cursor, $wrongCursor);

    $numResultSets = 1;
    while ($next = sqlsrv_next_result($stmt)) {
        checkColumnsAndRows($stmt, $cursor, $wrongCursor);
        
        while ($res = sqlsrv_fetch($stmt))
        { }
    
        checkColumnsAndRows($stmt, $cursor, $wrongCursor);
        ++$numResultSets;
    }
    
    if ($numResultSets != $expectedResultSets) {
        fatalError("Unexpected number of result sets, expected ".$expectedResultedSets.", got ".$numResultSets."\n");
    }

    // We expect an error if sqlsrv_next_result returns false,
    // but not if it returns null (i.e. if we are genuinely at
    // the end of all the result sets with a buffered cursor)
    if ($next === false) {
        if ($cursor == 'forward') {
            checkErrors($noNextResult);
        } else {
            fatalError("sqlsrv_next_result failed with a ".$cursor." cursor\n");
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
