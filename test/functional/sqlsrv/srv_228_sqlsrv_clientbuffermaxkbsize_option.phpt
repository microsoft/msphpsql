--TEST--
GitHub issue #228 - how ClientBufferMaxKBSize affects sqlsrv_has_rows and sqlsrv_fetch_array
--DESCRIPTION--
A variation of the example in GitHub issue 228, using ClientBufferMaxKBSize the statement option.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

require_once('MsCommon.inc');

function testErrors($conn, $table, $error)
{
    $query = "SELECT * FROM $table";
    
    // set client buffer size to 0KB 
    $stmt = sqlsrv_prepare($conn, $query, array(), array("Scrollable"=>"buffered", "ClientBufferMaxKBSize" => 0));
    if ($stmt !== false) {
        echo("Setting client buffer size to 0KB should have failed\n");
    } else {
        if (strpos(sqlsrv_errors()[0]['message'], $error) === false) {
            print_r(sqlsrv_errors());
        }
    }

    // set client buffer size to 0.99KB 
    $stmt = sqlsrv_prepare($conn, $query, array(), array("Scrollable"=>"buffered", "ClientBufferMaxKBSize" => 0.99));
    if ($stmt !== false) {
        echo("Setting client buffer size to 0.99KB should have failed\n");
    } else {
        if (strpos(sqlsrv_errors()[0]['message'], $error) === false) {
            print_r(sqlsrv_errors());
        }
    }
}

function fetchData($conn, $table, $size)
{
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $table", array(), array("Scrollable"=>"buffered", "ClientBufferMaxKBSize" => $size));
    
    $numRowsExpected = ($size > 1) ? 1 : 0;
    $res = sqlsrv_execute($stmt);
    if ($res && $size < 2) {
        echo "Expect this to fail\n";
    } else {
        $error = 'Memory limit of 1 KB exceeded for buffered query';
        $errors = sqlsrv_errors();
        if (!empty($errors) && strpos($errors[0]['message'], $error) === false) {
            print_r(sqlsrv_errors());
        }
    }
    
    $rows = sqlsrv_has_rows($stmt);
    if ($numRowsExpected && !$rows) {
        fatalError("sqlsrv_has_rows failed\n");
    }
    
    $numRowsFetched = 0;
    while ($row = sqlsrv_fetch_array($stmt)) {
        $numRowsFetched++;
    }
    if ($numRowsExpected != $numRowsFetched) {
        echo("Expected $numRowsExpected but number of rows fetched is $numRowsFetched\n");
    }
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
                 new AE\ColumnMeta('varchar(1050)', 'c2_varchar_1050'));
$stmt = AE\createTable($conn, $tableName2, $columns);

// insert > 1KB into c2_varchar_max & c2_varchar_1050 (1050 characters).
$longString = str_repeat('This is a test', 75);

$stmt = AE\insertRow($conn, $tableName1, array('c1_int' => 1, 'c2_varchar_max' => $longString));
$stmt = AE\insertRow($conn, $tableName2, array('c1_int' => 1, 'c2_varchar_1050' => $longString));
sqlsrv_free_stmt($stmt);

$error = 'Setting for ClientBufferMaxKBSize was non-int or non-positive';
testErrors($conn, $tableName1, $error);

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
Done
