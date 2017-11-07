--TEST--
Test with static cursor and select different rows in some random order
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function fetchRowQuery($conn)
{
    $tableName = 'testScrollCursor'; 

    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                 new AE\ColumnMeta('varchar(10)', 'c2_varchar'));
    AE\createTable($conn, $tableName, $columns);

    // insert data
    $numRows = 10;
    insertData($conn, $tableName, $numRows);

    // select table
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName", array(), array('Scrollable' => 'static'));

    hasRows($stmt);
    $numRowsFetched = 0;
    while ($obj = sqlsrv_fetch_object($stmt)) {
        echo $obj->c1_int . ", " . $obj->c2_varchar . "\n";
        $numRowsFetched++;
    }

    if ($numRowsFetched != $numRows) {
        echo "Number of rows fetched $numRowsFetched is wrong! Expected $numRows\n";
    }

    getFirstRow($stmt);
    getNextRow($stmt);
    getLastRow($stmt);
    getPriorRow($stmt);
    getAbsoluteRow($stmt, 7);
    getAbsoluteRow($stmt, 2);
    getRelativeRow($stmt, 3);
    getPriorRow($stmt);
    getRelativeRow($stmt, -4);
    getAbsoluteRow($stmt, 0);
    getNextRow($stmt);
    getRelativeRow($stmt, 5);
    getAbsoluteRow($stmt, -1);
    getNextRow($stmt);
    getLastRow($stmt);
    getRelativeRow($stmt, 1);
    
    dropTable($conn, $tableName);
}

function insertData($conn, $tableName, $numRows)
{
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_varchar) VALUES (?, ?)", array(&$v1, &$v2));

    for ($i = 0; $i < $numRows; $i++) {
        $v1 = $i + 1;
        $v2 = "Row " . $v1;

        sqlsrv_execute($stmt);
    }
}

function getFirstRow($stmt)
{
    echo "\nfirst row: ";
    $result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);
    if ($result) {
        $field1 = sqlsrv_get_field($stmt, 0);
        $field2 = sqlsrv_get_field($stmt, 1);
        echo "$field1, $field2\n";
    }
}

function getNextRow($stmt)
{
    echo "\nnext row: ";
    $result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_NEXT);
    if ($result) {
        $field1 = sqlsrv_get_field($stmt, 0);
        $field2 = sqlsrv_get_field($stmt, 1);
        echo "$field1, $field2\n";
    }
}

function getPriorRow($stmt)
{
    echo "\nprior row: ";
    $obj = sqlsrv_fetch_object($stmt, null, null, SQLSRV_SCROLL_PRIOR);
    if ($obj) {
        echo $obj->c1_int . ", " . $obj->c2_varchar . "\n";
    }
}

function getLastRow($stmt)
{
    echo "\nlast row: ";
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC, SQLSRV_SCROLL_LAST);
    if ($row) {
        echo $row[0] . ", " . $row[1] . "\n";
    }
}

function getRelativeRow($stmt, $offset)
{
    echo "\nrow $offset from the current row: ";
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_RELATIVE, $offset);
    if ($row) {
        echo $row['c1_int'] . ", " . $row['c2_varchar'] . "\n";
    }
}

function getAbsoluteRow($stmt, $offset)
{
    echo "\nabsolute row with offset $offset: ";
    $obj = sqlsrv_fetch_object($stmt, null, null, SQLSRV_SCROLL_ABSOLUTE, $offset);
    if ($obj) {
        echo $obj->c1_int . ", " . $obj->c2_varchar . "\n";
    }
}

function hasRows($stmt)
{
    $rows = sqlsrv_has_rows($stmt);
    if ($rows != true) {
        echo "Should have rows!\n";
    }
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

echo "\nTest begins...\n";

// Connect
$conn = AE\connect();
fetchRowQuery($conn);

sqlsrv_close($conn);
echo "\nDone\n";
endTest("sqlsrv_fetch_cursor_static_scroll");

?>
--EXPECT--
﻿﻿
Test begins...
1, Row 1
2, Row 2
3, Row 3
4, Row 4
5, Row 5
6, Row 6
7, Row 7
8, Row 8
9, Row 9
10, Row 10

first row: 1, Row 1

next row: 2, Row 2

last row: 10, Row 10

prior row: 9, Row 9

absolute row with offset 7: 8, Row 8

absolute row with offset 2: 3, Row 3

row 3 from the current row: 6, Row 6

prior row: 5, Row 5

row -4 from the current row: 1, Row 1

absolute row with offset 0: 1, Row 1

next row: 2, Row 2

row 5 from the current row: 7, Row 7

absolute row with offset -1: 
next row: 1, Row 1

last row: 10, Row 10

row 1 from the current row: 
Done
Test "sqlsrv_fetch_cursor_static_scroll" completed successfully.
