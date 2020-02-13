--TEST--
Prepare with cursor buffered and fetch a variety of types converted to different types
--DESCRIPTION--
Test various conversion functionalites for buffered queries with SQLSRV.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

function fetchAsUTF8($conn, $tableName, $inputs)
{
    $query = "SELECT * FROM $tableName";
    $stmt = sqlsrv_query($conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
    if (!$stmt) {
        fatalError("In fetchAsUTF8: failed to run query!");
    }

    if (sqlsrv_fetch($stmt, SQLSRV_FETCH_NUMERIC) === false) {
        fatalError("In fetchAsUTF8: failed to fetch the row from $tableName!");
    }

    // Fetch all fields as UTF-8 strings
    for ($i = 0; $i < count($inputs); $i++) {
        $f = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING('utf-8'));
        if ($i == 0) {
            if ($inputs[$i] !== hex2bin($f)) {
                var_dump($f);
            }
        } else {
            if ($f !== $inputs[$i]) {
                var_dump($f);
            }
        }
    }
}

function fetchArray($conn, $tableName, $inputs)
{
    $query = "SELECT * FROM $tableName";
    
    $stmt = sqlsrv_prepare($conn, $query, array(), array('Scrollable'=>SQLSRV_CURSOR_CLIENT_BUFFERED, 'ReturnDatesAsStrings' => true));
    if (!$stmt) {
        fatalError("In fetchArray: failed to prepare query!");
    }
    $res = sqlsrv_execute($stmt);
    if (!$res) {
        fatalError("In fetchArray: failed to execute query!");
    }

    // Fetch fields as an array
    $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
    if ($results === false) {
        fatalError("In fetchArray: failed to fetch the row from $tableName!");
    }
    
    var_dump($results);
}

function fetchAsFloats($conn, $tableName, $inputs)
{
    $query = "SELECT * FROM $tableName";
    $stmt = sqlsrv_query($conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED, 'ReturnDatesAsStrings' => true));
    if (!$stmt) {
        fatalError("In fetchAsFloats: failed to run query!");
    }

    if (sqlsrv_fetch($stmt, SQLSRV_FETCH_NUMERIC) === false) {
        fatalError("In fetchAsFloats: failed to fetch the row from $tableName!");
    }

    // Fetch all fields as floats
    for ($i = 0; $i < count($inputs); $i++) {
        $f = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_FLOAT);
        if ($f == false) {
            print_r(sqlsrv_errors());
        } else {
            var_dump($f);
        }
    }
}

function fetchAsInts($conn, $tableName, $inputs)
{
    $query = "SELECT * FROM $tableName";
    $stmt = sqlsrv_query($conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED, 'ReturnDatesAsStrings' => true));
    if (!$stmt) {
        fatalError("In fetchAsInts: failed to run query!");
    }

    if (sqlsrv_fetch($stmt, SQLSRV_FETCH_NUMERIC) === false) {
        fatalError("In fetchAsInts: failed to fetch the row from $tableName!");
    }

    // Fetch all fields as integers
    for ($i = 0; $i < count($inputs); $i++) {
        $f = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_INT);
        if ($f == false) {
            print_r(sqlsrv_errors());
        } else {
            var_dump($f);
        }
    }
}

function fetchAsBinary($conn, $tableName, $inputs)
{
    $query = "SELECT c_varbinary, c_varchar, c_nvarchar FROM $tableName";
    
    $stmt = sqlsrv_prepare($conn, $query, array(), array('Scrollable'=>SQLSRV_CURSOR_CLIENT_BUFFERED));
    if (!$stmt) {
        fatalError("In fetchAsBinary: failed to prepare query!");
    }
    $res = sqlsrv_execute($stmt);
    if (!$res) {
        fatalError("In fetchAsBinary: failed to execute query!");
    }

    if (sqlsrv_fetch($stmt, SQLSRV_FETCH_NUMERIC) === false) {
        fatalError("In fetchAsInts: failed to fetch the row from $tableName!");
    }

    // Fetch all fields as varbinary
    for ($i = 0; $i < 3; $i++) {
        $f = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STREAM("binary"));
        if (gettype($f) !== 'resource') {
            var_dump($f);
        }
        // Do not expect errors
        $errs = sqlsrv_errors();
        if (!empty($errs)) {
            var_dump($errs);
        }
        
        // Checks the first field only for this test
        while (!feof($f)) { 
            $str = fread($f, 80);
        }
        if ($i == 0) {
            if (trim($str) !== $inputs[0]) {
                echo "Fetched binary value unexpected: $str\n";
            }
        } else {
            print(bin2hex($str));
        }
    }
}

require_once('MsCommon.inc');

$conn = AE\connect(array('CharacterSet' => 'UTF-8'));
$tableName = 'testFetchingClientBuffer';

// Create table
$names = array('c_varbinary', 'c_int', 'c_float', 'c_decimal', 'c_datetime2', 'c_varchar', 'c_nvarchar');

$columns = array(new AE\ColumnMeta('varbinary(10)', $names[0]),
                 new AE\ColumnMeta('int', $names[1]),
                 new AE\ColumnMeta('float(53)', $names[2]),
                 new AE\ColumnMeta('decimal(16, 6)', $names[3]),
                 new AE\ColumnMeta('datetime2', $names[4]),
                 new AE\ColumnMeta('varchar(50)', $names[5]),
                 new AE\ColumnMeta('nvarchar(50)', $names[6]));
$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create $tableName!");
}

// Prepare the input values
$inputs = array('abcdefghij', '34567', '9876.5432', '123456789.012340', '2020-02-02 20:20:20.2220000', 'This is a test', 'Şơмė śäოрŀề');

$params = array(array(bin2hex($inputs[0]), SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_BINARY(10)),
                $inputs[1], $inputs[2], $inputs[3], $inputs[4], $inputs[5],
                array($inputs[6], SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8')));

// Form the insert query
$colStr = '(';
foreach ($names as $name) {
    $colStr .= $name . ", ";
}
$colStr = rtrim($colStr, ", ") . ") ";
$insertSql = "INSERT INTO [$tableName] " . $colStr . 'VALUES (?,?,?,?,?,?,?)';

// Insert one row only
$stmt = sqlsrv_prepare($conn, $insertSql, $params);
if ($stmt) {
    $res = sqlsrv_execute($stmt);
    if (!$res) {
        fatalError("Failed to execute insert statement to $tableName!");
    }
} else {
    fatalError("Failed to prepare insert statement to $tableName!");
}

// Starting fetching using client buffers
fetchAsUTF8($conn, $tableName, $inputs);
fetchArray($conn, $tableName, $inputs);
fetchAsFloats($conn, $tableName, $inputs);
fetchAsInts($conn, $tableName, $inputs);
fetchAsBinary($conn, $tableName, $inputs);

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
array(7) {
  ["c_varbinary"]=>
  string(10) "abcdefghij"
  ["c_int"]=>
  int(34567)
  ["c_float"]=>
  float(9876.5432)
  ["c_decimal"]=>
  string(16) "123456789.012340"
  ["c_datetime2"]=>
  string(27) "2020-02-02 20:20:20.2220000"
  ["c_varchar"]=>
  string(14) "This is a test"
  ["c_nvarchar"]=>
  string(23) "Şơмė śäოрŀề"
}
Array
(
    [0] => Array
        (
            [0] => 07006
            [SQLSTATE] => 07006
            [1] => 0
            [code] => 0
            [2] => Restricted data type attribute violation
            [message] => Restricted data type attribute violation
        )

)
float(34567)
float(9876.5432)
float(123456789.01234)
float(2020)
Array
(
    [0] => Array
        (
            [0] => 22003
            [SQLSTATE] => 22003
            [1] => 103
            [code] => 103
            [2] => Numeric value out of range
            [message] => Numeric value out of range
        )

)
Array
(
    [0] => Array
        (
            [0] => 22003
            [SQLSTATE] => 22003
            [1] => 103
            [code] => 103
            [2] => Numeric value out of range
            [message] => Numeric value out of range
        )

)
Array
(
    [0] => Array
        (
            [0] => 07006
            [SQLSTATE] => 07006
            [1] => 0
            [code] => 0
            [2] => Restricted data type attribute violation
            [message] => Restricted data type attribute violation
        )

)
int(34567)
Array
(
    [0] => Array
        (
            [0] => 01S07
            [SQLSTATE] => 01S07
            [1] => 0
            [code] => 0
            [2] => Fractional truncation
            [message] => Fractional truncation
        )

)
int(123456789)
int(2020)
Array
(
    [0] => Array
        (
            [0] => 22003
            [SQLSTATE] => 22003
            [1] => 103
            [code] => 103
            [2] => Numeric value out of range
            [message] => Numeric value out of range
        )

)
Array
(
    [0] => Array
        (
            [0] => 22003
            [SQLSTATE] => 22003
            [1] => 103
            [code] => 103
            [2] => Numeric value out of range
            [message] => Numeric value out of range
        )

)
