--TEST--
sqlsrv_has_rows() using a forward and scrollable cursor
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

require_once('MsCommon.inc');

function fetchData($conn, $table, $size)
{
    $ret = sqlsrv_configure('ClientBufferMaxKBSize', $size);
    var_dump($ret);
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $table", array(), array("Scrollable"=>"buffered"));
    $attr = sqlsrv_get_config('ClientBufferMaxKBSize');
    echo("ClientBufferMaxKBSize is $attr\n");

    sqlsrv_execute($stmt);
    $rows = sqlsrv_has_rows($stmt);
    var_dump($rows);

    sqlsrv_execute($stmt);
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
                 new AE\ColumnMeta('varchar(1036)', 'c2_varchar_1036'));
$stmt = AE\createTable($conn, $tableName2, $columns);

// insert > 1KB into c2_varchar_max & c2_varchar_1036 (1036 characters).
$longString = 'This is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a testThis is a test';

$stmt = AE\insertRow($conn, $tableName1, array('c1_int' => 1, 'c2_varchar_max' => $longString));
$stmt = AE\insertRow($conn, $tableName2, array('c1_int' => 1, 'c2_varchar_1036' => $longString));

// set client buffer size to 0KB returns false
$ret = sqlsrv_configure('ClientBufferMaxKBSize', 0);
var_dump($ret);

// set client buffer size to 1KB
$size = 1;
fetchData($conn, $tableName1, $size); // this should return 0 rows.
fetchData($conn, $tableName2, $size); // this should return 0 rows.
// set client buffer size to 2KB
$size = 2;
fetchData($conn, $tableName1, $size); // this should return 1 row.
fetchData($conn, $tableName2, $size); // this should return 1 row.

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
bool(false)
bool(true)
ClientBufferMaxKBSize is 1
bool(false)
Number of rows fetched: 0
bool(true)
ClientBufferMaxKBSize is 1
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
