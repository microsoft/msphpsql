--TEST--
Test a batch query with different cursor types
--DESCRIPTION--
Verifies row and column counts from batch queries. This is the
equivalent of sqlsrv_batch_query.phpt on the sqlsrv side.
TODO: Fix this test once error reporting in PDO is fixed, because batch
queries are not supposed to work with server side cursors. For now, no errors
or warnings are returned. For information on the expected behaviour of cursors
with batch queries, see
https://docs.microsoft.com/en-us/previous-versions/visualstudio/aa266531(v=vs.60)
--SKIPIF--
<?php 
require('skipif_mid-refactor.inc'); 
?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsSetup.inc");

// All supported cursor types
$cursors = array(array(PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY),
                 array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_DYNAMIC),
                 array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_STATIC),
                 array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_KEYSET),
                 array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED),
                );

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
    // TODO: Fill this in once PDO error reporting is fixed
}

function checkColumnsAndRows($stmt, $cursor, $before)
{
    global $expectedCols, $expectedRows;
    
    $cols = $stmt->columnCount();
    
    if ($cols != $expectedCols) {
        fatalError("Incorrect number of columns returned with $cursor cursor. Expected $expectedCols columns, got $cols columns\n");
    }

    $rows = $stmt->rowCount();

    // Buffered cursors always return the correct number of rows. Other cursors
    // return -1 rows before fetching. Static and keyset cursors return -1 even
    // after fetching, while forward and dynamic cursors return the correct
    // number of rows after fetching.
    if ($cursor == 'buffered') {
        if ($rows != $expectedRows) {
            fatalError("Incorrect number of columns returned with buffered cursor. Expected $expectedRows rows, got $rows rows\n");
        }
    } else {
        if ($before) {
            if ($rows !== -1) {
                fatalError("Incorrect number of rows returned before fetching with a $cursor cursor. Expected -1 rows, got $rows rows\n");
            }
        } else {
            if ($cursor == 'static' or $cursor == 'keyset') {
                if ($rows !== -1) {
                    fatalError("Incorrect number of rows returned before fetching with a $cursor cursor. Expected -1 rows, got $rows rows\n");
                }
            } else {
                if ($rows != $expectedRows) {
                    fatalError("Incorrect number of columns returned with buffered cursor. Expected $expectedRows rows, got $rows rows\n");
                }
            }
        }
    }
}

function printCursor($element)
{
    $cursor = 'forward';
    switch($element) {
        case 0:
            echo "Testing with forward cursor...\n";
            break;
        case 1:
            echo "Testing with dynamic cursor...\n";
            $cursor = 'dynamic';
            break;
        case 2:
            echo "Testing with static cursor...\n";
            $cursor = 'static';
            break;
        case 3:
            echo "Testing with keyset cursor...\n";
            $cursor = 'keyset';
            break;
        case 4:
            echo "Testing with buffered cursor...\n";
            $cursor = 'buffered';
            break;
        default:
            fatalError("Unknown cursor type! Exiting\n");
    }
    return $cursor;
}

$conn = connect();
$conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Create and populate a table of integer types
$tableName = 'batch_query_test';
$columns = array(new ColumnMeta('int', $colName[0]),
                 new ColumnMeta('tinyint', $colName[1]),
                 new ColumnMeta('smallint',$colName[2]),
                 new ColumnMeta('bigint', $colName[3]),
                 new ColumnMeta('bit', $colName[4]));

createTable($conn, $tableName, $columns);

// Insert each row. Need an associative array to use insertRow()
for ($i = 0; $i < $expectedRows; ++$i) {
    $inputs = array();
    for ($j = 0; $j < $expectedResultSets; ++$j) {
        $inputs[$colName[$j]] = $data[$j][$i];
    }

    $stmt = insertRow($conn, $tableName, $inputs);
    unset($inputs);
    unset($stmt);
}

$query = "SELECT c1_int FROM $tableName;
          SELECT c2_tinyint FROM $tableName;
          SELECT c3_smallint FROM $tableName;
          SELECT c4_bigint FROM $tableName;
          SELECT c5_bit FROM $tableName;";

// Test the batch query with different cursor types
for ($i = 0; $i < sizeof($cursors); ++$i) {
    try {
        $cursorType = $cursors[$i];
        $cursor = printCursor($i);

        $stmt = $conn->prepare($query, $cursorType);
        $stmt->execute();

        $numResultSets = 0;

        // Check the column and row count before and after running through
        // each result set, because some cursor types may return the number
        // of rows only after fetching all rows in the result set
        do {
            checkColumnsAndRows($stmt, $cursor, true);

            $row = 0;
            while ($res = $stmt->fetch(PDO::FETCH_NUM)) {
                if ($res[0] != $data[$numResultSets][$row]) {
                    fatalError("Wrong result, expected ".$data[$numResultSets][$row].", got $res[0]\n");
                }
                ++$row;
            }

            checkColumnsAndRows($stmt, $cursor, false);
            ++$numResultSets;
        } while ($next = $stmt->nextRowset());

        if ($numResultSets != $expectedResultSets) {
            fatalError("Unexpected number of result sets, expected $expectedResultedSets, got $numResultSets\n");
        }
    } catch(PDOException $e) {
        echo "Exception caught\n";
        print_r($e);
    }

unset($stmt);
}

dropTable($conn, $tableName);
unset($conn);

echo "Done.\n";
?>
--EXPECT--
Testing with forward cursor...
Testing with dynamic cursor...
Testing with static cursor...
Testing with keyset cursor...
Testing with buffered cursor...
Done.
