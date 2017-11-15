--TEST--
Test various cursor types and whether they reflect changes in the database
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function fetchWithCursor($conn, $cursorType)
{
    $tableName = "table_$cursorType"; 

    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('char(10)', 'c2_char'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    
    // insert four rows
    $numRows = 4;
    insertData($conn, $tableName, 0, $numRows);

    // select table
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName", array(), array('Scrollable' => $cursorType));
    sqlsrv_execute($stmt);

    getNumRows($stmt, $cursorType);
    $numRowsFetched = 0;
    while ($obj = sqlsrv_fetch_object($stmt)) {
        echo $obj->c1_int . "\n";
        $numRowsFetched++;
    }

    if ($numRowsFetched != $numRows) {
        echo "Number of rows fetched $numRowsFetched is wrong! Expected $numRows\n";
    }

    deleteThenFetchLastRow($conn, $stmt, $tableName, 4);
    
    dropTable($conn, $tableName);
}

function insertData($conn, $tableName, $start, $count)
{
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_char) VALUES (?, ?)", array(&$v1, &$v2));

    $numRows = $start + $count;
    for ($i = $start; $i < $numRows; $i++) {
        $v1 = $i + 1;
        $v2 = "Row " . $v1;

        sqlsrv_execute($stmt);
    }
}

function deleteThenFetchLastRow($conn, $stmt, $tableName, $id)
{
    echo "\nNow delete the last row then try to fetch it...\n";
    $stmt2 = AE\executeQuery($conn, "DELETE FROM $tableName", "[c1_int] = ?", array(4));

    $result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_LAST);
    if ($result) {
        $field1 = sqlsrv_get_field($stmt, 0);
        $field2 = sqlsrv_get_field($stmt, 1);
        var_dump($field1);
        var_dump($field2);
    } else {
        var_dump($result);
    }
}

function getNumRows($stmt, $cursorType)
{
    $expectedToFail = false;
    if ($cursorType == SQLSRV_CURSOR_FORWARD || $cursorType == SQLSRV_CURSOR_DYNAMIC) {
        $expectedToFail = true;
    }

    $rowCount = 0;
    $rowCount = sqlsrv_num_rows($stmt);
    if ($expectedToFail) {
        if ($rowCount === false) {
            echo "Error occurred in sqlsrv_num_rows, which is expected\n";
        } else {
            echo "sqlsrv_num_rows expected to fail!\n";
        }
    } else {
        if ($rowCount === false) {
            echo "Error occurred in sqlsrv_num_rows, which is unexpected!\n";
        } else {
            echo "Number of rows: $rowCount\n";
        }
    }
}

try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // Connect
    $conn = AE\connect();

    echo "\nUsing SQLSRV_CURSOR_FORWARD...\n";
    fetchWithCursor($conn, SQLSRV_CURSOR_FORWARD);
    echo "\nUsing SQLSRV_CURSOR_DYNAMIC...\n";
    fetchWithCursor($conn, SQLSRV_CURSOR_DYNAMIC);
    echo "\nUsing SQLSRV_CURSOR_KEYSET...\n";
    fetchWithCursor($conn, SQLSRV_CURSOR_KEYSET);
    echo "\nUsing SQLSRV_CURSOR_STATIC...\n";
    fetchWithCursor($conn, SQLSRV_CURSOR_STATIC);

    sqlsrv_close($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";
endTest("sqlsrv_fetch_cursor_types");

?>
--EXPECT--

Using SQLSRV_CURSOR_FORWARD...
Error occurred in sqlsrv_num_rows, which is expected
1
2
3
4

Now delete the last row then try to fetch it...
bool(false)

Using SQLSRV_CURSOR_DYNAMIC...
Error occurred in sqlsrv_num_rows, which is expected
1
2
3
4

Now delete the last row then try to fetch it...
int(3)
string(10) "Row 3     "

Using SQLSRV_CURSOR_KEYSET...
Number of rows: 4
1
2
3
4

Now delete the last row then try to fetch it...
bool(false)
bool(false)

Using SQLSRV_CURSOR_STATIC...
Number of rows: 4
1
2
3
4

Now delete the last row then try to fetch it...
int(4)
string(10) "Row 4     "

Done
Test "sqlsrv_fetch_cursor_types" completed successfully.
