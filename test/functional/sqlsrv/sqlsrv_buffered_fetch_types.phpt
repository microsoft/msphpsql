--TEST--
Prepare with cursor buffered and fetch a variety of types converted to different types
--DESCRIPTION--
Test various conversion functionalites for buffered queries with SQLSRV.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

$violation = 'Restricted data type attribute violation';
$outOfRange = 'Numeric value out of range';
$truncation = 'Fractional truncation';

function compareFloats($expected, $actual)
{
    $epsilon = 0.00001;
    
    $diff = abs(($actual - $expected) / $expected);
    
    return ($diff < $epsilon);
}

function fetchAsChar($conn, $tableName, $inputs)
{
    $query = "SELECT c_varbinary, c_int, c_float, c_decimal, c_datetime2, c_varchar FROM $tableName";
    
    $stmt = sqlsrv_query($conn, $query, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
    if (!$stmt) {
        fatalError("In fetchAsChar: failed to run query!");
    }

    if (sqlsrv_fetch($stmt, SQLSRV_FETCH_NUMERIC) === false) {
        fatalError("In fetchAsChar: failed to fetch the row from $tableName!");
    }

    // Fetch all fields as strings - no conversion
    for ($i = 0; $i < count($inputs) - 1; $i++) {
        $f = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        if ($i == 0) {
            if ($inputs[$i] !== hex2bin($f)) {
                echo "In fetchAsChar ($i): expected $inputs[$i]\n";
                var_dump(hex2bin($f));
            }
        } elseif ($i == 2) {
            if (!compareFloats(floatval($inputs[$i]), floatval($f))) {
                echo "In fetchAsChar ($i): expected $inputs[$i]\n";
                var_dump($f);
            }
        } else {
            if ($f !== $inputs[$i]) {
                echo "In fetchAsChar ($i): expected $inputs[$i]\n";
                var_dump($f);
            }
        }
    }
}

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
                echo "In fetchAsUTF8 ($i): expected $inputs[$i]\n";
                var_dump(hex2bin($f));
            }
        } elseif ($i == 2) {
            if (!compareFloats(floatval($inputs[$i]), floatval($f))) {
                echo "In fetchAsUTF8 ($i): expected $inputs[$i]\n";
                var_dump($f);
            }
        } else {
            if ($f !== $inputs[$i]) {
                echo "In fetchAsUTF8 ($i): expected $inputs[$i]\n";
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
    $results = sqlsrv_fetch_array($stmt);
    if ($results === false) {
        fatalError("In fetchArray: failed to fetch the row from $tableName!");
    }
    
    for ($i = 0; $i < count($inputs); $i++) {
        $matched = true;
        if ($i == 1) {
            $expected = intval($inputs[$i]);
            if ($results[$i] !== $expected) {
                $matched = false;
            }
        } elseif ($i == 2) {
            $expected = floatval($inputs[$i]);
            if (!compareFloats($expected, $results[$i])) {
                $matched = false;
            }
        } else {
            $expected = $inputs[$i];
            if ($results[$i] !== $expected) {
                $matched = false;
            }
        }

        // if ($results[$i] !== $expected) {
        if (!$matched) {
            echo "in fetchArray: for column $i expected $expected but got: ";
            var_dump($results[$i]);
        }
    }
}

function fetchAsFloats($conn, $tableName, $inputs)
{
    global $violation, $outOfRange, $epsilon;
    
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

        if ($i == 0) {
            // The varbinary field - expect the violation error
            if (strpos(sqlsrv_errors()[0]['message'], $violation) === false) {
                var_dump($f);
                fatalError("in fetchAsFloats: expected $violation for column $i\n");
            }
        } elseif ($i < 5) {
            $expected = floatval($inputs[$i]);
            if (!compareFloats($expected, $f)) {
                echo "in fetchAsFloats: for column $i expected $expected but got: ";
                var_dump($f);
            }
        } else {
            // The char fields will get errors too
            if (strpos(sqlsrv_errors()[0]['message'], $outOfRange) === false) {
                var_dump($f);
                fatalError("in fetchAsFloats: expected $outOfRange for column $i\n");
            }
        }
    }
}

function fetchAsInts($conn, $tableName, $inputs)
{
    global $violation, $outOfRange, $truncation;

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
        
        if ($i == 0) {
            // The varbinary field - expect the violation error
            if (strpos(sqlsrv_errors()[0]['message'], $violation) === false) {
                var_dump($f);
                fatalError("in fetchAsInts: expected $violation for column $i\n");
            }
        } elseif ($i == 2) {
            // The float field - expect truncation
            if (strpos(sqlsrv_errors()[0]['message'], $truncation) === false) {
                var_dump($f);
                fatalError("in fetchAsInts: expected $truncation for column $i\n");
            }
        } elseif ($i >= 5) {
            // The char fields will get errors too
            if (strpos(sqlsrv_errors()[0]['message'], $outOfRange) === false) {
                var_dump($f);
                fatalError("in fetchAsInts: expected $outOfRange for column $i\n");
            }
        } else {
            $expected = floor(floatval($inputs[$i]));
            if ($f != $expected) {
                echo "in fetchAsInts: for column $i expected $expected but got: ";
                var_dump($f);
            }
        }
    }
}

function fetchAsBinary($conn, $tableName, $inputs)
{
    $query = "SELECT c_varbinary FROM $tableName";
    
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

    // Fetch the varbinary field as is
    $f = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM("binary"));
    if (gettype($f) !== 'resource') {
        var_dump($f);
    }
    // Do not expect errors
    $errs = sqlsrv_errors();
    if (!empty($errs)) {
        var_dump($errs);
    }
    
    // Check its value
    while (!feof($f)) { 
        $str = fread($f, 80);
    }
    if (trim($str) !== $inputs[0]) {
        echo "Fetched binary value unexpected: $str\n";
    }
}

require_once('MsCommon.inc');

$conn = AE\connect(array('CharacterSet' => 'UTF-8'));
$tableName = 'srvFetchingClientBuffer';

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
fetchAsChar($conn, $tableName, $inputs);
fetchAsUTF8($conn, $tableName, $inputs);
fetchArray($conn, $tableName, $inputs);
fetchAsFloats($conn, $tableName, $inputs);
fetchAsInts($conn, $tableName, $inputs);
fetchAsBinary($conn, $tableName, $inputs);

dropTable($conn, $tableName);

echo "Done\n";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

?>
--EXPECT--
Done
