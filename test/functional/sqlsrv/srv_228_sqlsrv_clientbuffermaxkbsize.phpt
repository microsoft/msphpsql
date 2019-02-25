--TEST--
GitHub issue #228 - how ClientBufferMaxKBSize affects sqlsrv_has_rows and sqlsrv_fetch_array
--DESCRIPTION--
Based on the example in GitHub issue 228, configuring ClientBufferMaxKBSize with sqlsrv_configure.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

require_once('MsCommon.inc');

function testErrors($conn)
{
    // set client buffer size to 0KB returns false
    $ret = sqlsrv_configure('ClientBufferMaxKBSize', 0);
    if (!$ret) {
        echo sqlsrv_errors()[0]['message'] . "\n";
    }

    $ret = sqlsrv_configure('ClientBufferMaxKBSize', -1.9);
    if (!$ret) {
        echo sqlsrv_errors()[0]['message'] . "\n";
    }
}

function fetchData($conn, $table, $size)
{
    $ret = sqlsrv_configure('ClientBufferMaxKBSize', $size);
    var_dump($ret);
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $table", array(), array("Scrollable"=>"buffered"));
    $attr = sqlsrv_get_config('ClientBufferMaxKBSize');
    echo("ClientBufferMaxKBSize is $attr\n");

    sqlsrv_execute($stmt);
    if ($size < 2) {
        echo sqlsrv_errors()[0]['message'] . "\n";
    }
    
    $rows = sqlsrv_has_rows($stmt);
    var_dump($rows);

    $numRowsFetched = 0;
    while ($row = sqlsrv_fetch_array($stmt)) {
        $numRowsFetched++;
    }
    echo("Number of rows fetched: $numRowsFetched\n");
}

// connect
$conn = AE\connect();

$tableName1 = 'php_test_table_1';
$tableName2 = 'php_test_table_2';

// Create tables
$columns = array(new AE\ColumnMeta('int', 'c1_int'),
                 new AE\ColumnMeta('varchar(max)', 'c2_varchar_max'));
$stmt = AE\createTable($conn, $tableName1, $columns);

unset($columns);
$columns = array(new AE\ColumnMeta('int', 'c1_int'),
                 new AE\ColumnMeta('varchar(1400)', 'c2_varchar_1400'));
$stmt = AE\createTable($conn, $tableName2, $columns);

// insert > 1KB into c2_varchar_max & c2_varchar_1400 (1400 characters).
$longString = str_repeat('This is a test', 100);

$stmt = AE\insertRow($conn, $tableName1, array('c1_int' => 1, 'c2_varchar_max' => $longString));
$stmt = AE\insertRow($conn, $tableName2, array('c1_int' => 1, 'c2_varchar_1400' => $longString));
sqlsrv_free_stmt($stmt);

testErrors($conn);

// set client buffer size to 1KB
$size = 1;
fetchData($conn, $tableName1, $size); // this should return 0 rows.
fetchData($conn, $tableName2, $size); // this should return 0 rows.
// set client buffer size to 2KB
$size = 2;
fetchData($conn, $tableName1, $size); // this should return 1 row.
fetchData($conn, $tableName2, $size); // this should return 1 row.

dropTable($conn, $tableName1);
dropTable($conn, $tableName2);

sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
Setting for ClientBufferMaxKBSize was non-int or non-positive.
Setting for ClientBufferMaxKBSize was non-int or non-positive.
bool(true)
ClientBufferMaxKBSize is 1
Memory limit of 1 KB exceeded for buffered query
bool(false)
Number of rows fetched: 0
bool(true)
ClientBufferMaxKBSize is 1
Memory limit of 1 KB exceeded for buffered query
bool(false)
Number of rows fetched: 0
bool(true)
ClientBufferMaxKBSize is 2
bool(true)
Number of rows fetched: 1
bool(true)
ClientBufferMaxKBSize is 2
bool(true)
Number of rows fetched: 1
Done
