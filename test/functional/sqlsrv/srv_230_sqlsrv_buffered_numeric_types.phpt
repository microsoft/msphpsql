--TEST--
Read numeric types from SQLSRV with buffered query.
--DESCRIPTION--
Test numeric conversion (number to string, string to number) functionality for buffered queries with SQLSRV.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

function getInputData($inputs)
{
    return array('a' => $inputs[0],
                 'neg_a'=> $inputs[1], 
                 'b' => $inputs[2], 
                 'neg_b' => $inputs[3], 
                 'c' => $inputs[4], 
                 'neg_c' => $inputs[5], 
                 'zero' => $inputs[6], 
                 'zerof' => $inputs[7], 
                 'zerod' => $inputs[8]);
}

require_once('MsCommon.inc');

$conn = AE\connect(array("CharacterSet"=>"utf-8"));
$tableName = 'test230';

$sample = 1234567890.1234;
$sample1 = -1234567890.1234;
$sample2 = 1;
$sample3 = -1;
$sample4 = 0.5;
$sample5 = -0.55;

// Create table
$columns = array(new AE\ColumnMeta('float(53)', 'a'),
                 new AE\ColumnMeta('float(53)', 'neg_a'),
                 new AE\ColumnMeta('int', 'b'),
                 new AE\ColumnMeta('int', 'neg_b'),
                 new AE\ColumnMeta('decimal(16, 6)', 'c'),
                 new AE\ColumnMeta('decimal(16, 6)', 'neg_c'),
                 new AE\ColumnMeta('int', 'zero'),
                 new AE\ColumnMeta('float(53)', 'zerof'),
                 new AE\ColumnMeta('decimal(16, 6)', 'zerod'));
AE\createTable($conn, $tableName, $columns);

$res = null;
$params = array($sample, $sample1, $sample2, $sample3, $sample4, $sample5, 0, 0, 0);
$data = getInputData($params);
$stmt = AE\insertRow($conn, $tableName, $data, $res, AE\INSERT_QUERY_PARAMS);
if (!$stmt) {
    fatalError("Failed to insert into $tableName!");
}

$params = array($sample4, $sample5, 100000, -1234567, $sample, $sample1, 0, 0, 0);
$data = getInputData($params);
$stmt = AE\insertRow($conn, $tableName, $data, $res, AE\INSERT_QUERY_PARAMS);
if (!$stmt) {
    fatalError("Failed to insert into $tableName!");
}

$query = "SELECT TOP 2 * FROM $tableName";
$stmt = sqlsrv_query($conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));

$array = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
var_dump($array);
$array = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
var_dump($array);

$numFields = sqlsrv_num_fields($stmt);
$meta = sqlsrv_field_metadata($stmt);
$rowcount = sqlsrv_num_rows($stmt);
for ($i = 0; $i < $rowcount; $i++) {
    sqlsrv_fetch($stmt, SQLSRV_SCROLL_ABSOLUTE, $i);
    for ($j = 0; $j < $numFields; $j++) {
        $name = $meta[$j]["Name"];
        print("\ncolumn: $name\n");
        $field = sqlsrv_get_field($stmt, $j, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        var_dump($field);
        if ($meta[$j]["Type"] == SQLSRV_SQLTYPE_INT) {
            $field = sqlsrv_get_field($stmt, $j, SQLSRV_PHPTYPE_INT);
            var_dump($field);
        }
        $field = sqlsrv_get_field($stmt, $j, SQLSRV_PHPTYPE_FLOAT);
        var_dump($field);
    }
}

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
array(9) {
  [0]=>
  float(1234567890.1234)
  [1]=>
  float(-1234567890.1234)
  [2]=>
  int(1)
  [3]=>
  int(-1)
  [4]=>
  string(7) ".500000"
  [5]=>
  string(8) "-.550000"
  [6]=>
  int(0)
  [7]=>
  float(0)
  [8]=>
  string(7) ".000000"
}
array(9) {
  [0]=>
  float(0.5)
  [1]=>
  float(-0.55)
  [2]=>
  int(100000)
  [3]=>
  int(-1234567)
  [4]=>
  string(17) "1234567890.123400"
  [5]=>
  string(18) "-1234567890.123400"
  [6]=>
  int(0)
  [7]=>
  float(0)
  [8]=>
  string(7) ".000000"
}

column: a
string(15) "1234567890.1234"
float(1234567890.1234)

column: neg_a
string(16) "-1234567890.1234"
float(-1234567890.1234)

column: b
string(1) "1"
int(1)
float(1)

column: neg_b
string(2) "-1"
int(-1)
float(-1)

column: c
string(7) ".500000"
float(0.5)

column: neg_c
string(8) "-.550000"
float(-0.55)

column: zero
string(1) "0"
int(0)
float(0)

column: zerof
string(1) "0"
float(0)

column: zerod
string(7) ".000000"
float(0)

column: a
string(3) "0.5"
float(0.5)

column: neg_a
string(5) "-0.55"
float(-0.55)

column: b
string(6) "100000"
int(100000)
float(100000)

column: neg_b
string(8) "-1234567"
int(-1234567)
float(-1234567)

column: c
string(17) "1234567890.123400"
float(1234567890.1234)

column: neg_c
string(18) "-1234567890.123400"
float(-1234567890.1234)

column: zero
string(1) "0"
int(0)
float(0)

column: zerof
string(1) "0"
float(0)

column: zerod
string(7) ".000000"
float(0)
