--TEST--
Test connection and statement attributes for formatting decimal and numeric data (feature request issue 415)
--DESCRIPTION--
Test the connection and statement options, FormatDecimals and
DecimalPlaces, the latter affects money types only, not
decimal or numeric types (feature request issue 415).
Money, decimal or numeric types are always fetched as strings to preserve accuracy and precision, unlike other primitive numeric types, where there is an option to retrieve them as numbers.

Setting FormatDecimals to false will turn off all formatting, regardless of DecimalPlaces value. Also, any negative DecimalPlaces value will be ignored. Likewise, since money or smallmoney fields have scale 4, if DecimalPlaces value is larger than 4, it will be ignored as well.

1. By default, data will be returned with the original precision and scale
2. Set FormatDecimals to true to add the leading zeroes to money and decimal types, if missing.
3. For output params, leading zeroes will be added for any decimal fields if FormatDecimals is true, but only if either SQLSRV_SQLTYPE_DECIMAL or SQLSRV_SQLTYPE_NUMERIC is set correctly to match the original column type and its precision / scale.

FormatDecimals and DecimalPlaces will only format the fetched results and have no effect on other operations like insertion or update.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function compareNumbers($actual, $input, $column, $fieldScale, $format = true)
{
    $matched = false;
    if ($actual === $input) {
        $matched = true;
        trace("Matched: $actual, $input\n");
    } else {
        // If no formatting, there will be no leading zero
        $expected = number_format($input, $fieldScale);
        if (!$format) {
            if (abs($input) < 1) {
                // Since no formatting, the leading zero should not be there
                trace("Drop leading zero of $input: ");
                $expected = str_replace('0.', '.', $expected);
            }
        }
        trace("With number_format: $actual, $expected\n");
        if ($actual === $expected) {
            $matched = true;
        } else {
            echo "For $column ($fieldScale): expected $expected ($input) but the value is $actual\n";
        }
    }
    return $matched;
}

function testErrorCases($conn)
{
    $query = "SELECT 0.0001";
    $message = 'Expected an integer to specify number of decimals to format the output values of decimal data types.';

    $options = array('DecimalPlaces' => 1.5);
    $stmt = sqlsrv_query($conn, $query, array(), $options);
    if ($stmt) {
        fatalError("Case 1: expected query to fail!!");
    } else {
        $error = sqlsrv_errors()[0]['message'];
        if (strpos($error, $message) === false) {
            print_r(sqlsrv_errors());
        }
    }

    $options = array('DecimalPlaces' => true);
    $stmt = sqlsrv_query($conn, $query, array(), $options);
    if ($stmt) {
        fatalError("Case 2: expected query to fail!!");
    } else {
        $error = sqlsrv_errors()[0]['message'];
        if (strpos($error, $message) === false) {
            print_r(sqlsrv_errors());
        }
    }
}

function testNoOption($conn, $tableName, $inputs, $columns, $exec)
{
    // This should return decimal values as they are
    $query = "SELECT * FROM $tableName";
    if ($exec) {
        $stmt = sqlsrv_query($conn, $query);
    } else {
        $stmt = sqlsrv_prepare($conn, $query);
        sqlsrv_execute($stmt);
    }

    // Compare values
    $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    for ($i = 0; $i < count($inputs); $i++) {
        compareNumbers($results[$i], $inputs[$i], $columns[$i], $i, false);
    }
}

function testStmtOption($conn, $tableName, $inputs, $columns, $decimalPlaces, $withBuffer)
{
    // Decimal values should NOT be affected by the statement
    // option DecimalPlaces
    $query = "SELECT * FROM $tableName";
    if ($withBuffer){
        $options = array('Scrollable' => 'buffered', 'DecimalPlaces' => $decimalPlaces);
    } else {
        $options = array('DecimalPlaces' => $decimalPlaces);
    }

    $size = count($inputs);
    $stmt = sqlsrv_prepare($conn, $query, array(), $options);

    // Fetch by getting one field at a time
    sqlsrv_execute($stmt);

    if (sqlsrv_fetch($stmt) === false) {
        fatalError("Failed in retrieving data\n");
    }
    for ($i = 0; $i < $size; $i++) {
        $field = sqlsrv_get_field($stmt, $i);   // Expect a string
        compareNumbers($field, $inputs[$i], $columns[$i], $i, true);
    }
}

function getOutputParam($conn, $storedProcName, $inputValue, $prec, $scale, $numeric, $inout)
{
    $outString = '';
    $numDigits = 2;
    $dir = SQLSRV_PARAM_OUT;

    // The output param value should be the same as the input,
    // unaffected by the statement attr DecimalPlaces. If 
    // the correct sql type is specified or ColumnEncryption
    // is enabled, in which case the driver is able to derive
    // the correct field type, leading zero will be added 
    // if missing
    $sqlType = null;
    if (!AE\isColEncrypted()) {
        $type = ($numeric) ? 'SQLSRV_SQLTYPE_NUMERIC' : 'SQLSRV_SQLTYPE_DECIMAL';
        $sqlType = call_user_func($type, $prec, $scale);
    }

    // For inout parameters the input type should match the output one
    if ($inout) {
        $dir = SQLSRV_PARAM_INOUT;
        $outString = '0.0';
    }

    $outSql = AE\getCallProcSqlPlaceholders($storedProcName, 1);
    $stmt = sqlsrv_prepare($conn, $outSql,
                            array(array(&$outString, $dir, null, $sqlType)),
                            array('DecimalPlaces' => $numDigits));
    if (!$stmt) {
        fatalError("getOutputParam: failed when preparing to call $storedProcName");
    }
    if (!sqlsrv_execute($stmt)) {
        fatalError("getOutputParam: failed to execute procedure $storedProcName");
    }

    // Verify value of output param 
    $column = 'outputParam';
    compareNumbers($outString, $inputValue, $column, $scale, true);
    sqlsrv_free_stmt($stmt);

    if (!AE\isColEncrypted()) {
        // With ColumnEncryption enabled, the driver is able to derive the decimal type,
        // so skip this part of the test
        $outString2 = $inout ? '0.0' : '';
        $stmt = sqlsrv_prepare($conn, $outSql,
                                array(array(&$outString2, $dir)),
                                array('DecimalPlaces' => $numDigits));
        if (!$stmt) {
            fatalError("getOutputParam2: failed when preparing to call $storedProcName");
        }
        if (!sqlsrv_execute($stmt)) {
            fatalError("getOutputParam2: failed to execute procedure $storedProcName");
        }

        $column = 'outputParam2';
        compareNumbers($outString2, $inputValue, $column, $scale, true);
        sqlsrv_free_stmt($stmt);
    }
}

function testOutputParam($conn, $tableName, $inputs, $columns, $dataTypes, $inout = false)
{
    for ($i = 0, $p = 3; $i < count($columns); $i++, $p++) {
        // Create the stored procedure first
        $storedProcName = "spFormatDecimals" . $i;
        $procArgs = "@col $dataTypes[$i] OUTPUT";
        $procCode = "SELECT @col = $columns[$i] FROM $tableName";
        createProc($conn, $storedProcName, $procArgs, $procCode);

        // Call stored procedure to retrieve output param
        getOutputParam($conn, $storedProcName, $inputs[$i], $p, $i, $i > 2, $inout);

        dropProc($conn, $storedProcName);
    }
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

$conn = AE\connect();
if (!$conn) {
    fatalError("Could not connect.\n");
}

// Test error conditions
testErrorCases($conn);

// Create the test table of decimal / numeric data columns
$tableName = 'sqlsrvFormatDecimals';

$columns = array('c1', 'c2', 'c3', 'c4', 'c5', 'c6');
$dataTypes = array('decimal(3,0)', 'decimal(4,1)', 'decimal(5,2)', 'numeric(6,3)', 'numeric(7,4)', 'numeric(8, 5)');

$colMeta = array(new AE\ColumnMeta($dataTypes[0], $columns[0]),
                 new AE\ColumnMeta($dataTypes[1], $columns[1]),
                 new AE\ColumnMeta($dataTypes[2], $columns[2]),
                 new AE\ColumnMeta($dataTypes[3], $columns[3]),
                 new AE\ColumnMeta($dataTypes[4], $columns[4]),
                 new AE\ColumnMeta($dataTypes[5], $columns[5]));
AE\createTable($conn, $tableName, $colMeta);

// Generate random input values based on precision and scale
$values = array();
$max2 = 1;
for ($s = 0, $p = 3; $s < count($columns); $s++, $p++) {
    // First get a random number
    $n = rand(1, 6);
    $neg = ($n % 2 == 0) ? -1 : 1;

    // $n1 is a tiny number, which may or may not be negative
    $max1 = 5;
    $n1 = rand(0, $max1) * $neg;

    if ($s > 0) {
        $max2 *= 10;
        $n2 = rand(0, $max2);
        $number = sprintf("%d.%d", $n1, $n2);
    } else {
        $number = sprintf("%d", $n1);
    }

    array_push($values, $number);
}

// Insert data values as strings
$inputData = array($colMeta[0]->colName => $values[0],
                   $colMeta[1]->colName => $values[1],
                   $colMeta[2]->colName => $values[2],
                   $colMeta[3]->colName => $values[3],
                   $colMeta[4]->colName => $values[4],
                   $colMeta[5]->colName => $values[5]);
$stmt = AE\insertRow($conn, $tableName, $inputData);
if (!$stmt) {
    var_dump($values);
    fatalError("Failed to insert data.\n");
}
sqlsrv_free_stmt($stmt);

testNoOption($conn, $tableName, $values, $columns, true);
testNoOption($conn, $tableName, $values, $columns, false);

sqlsrv_close($conn);

// Reconnect with FormatDecimals option set to true
$conn = AE\connect(array('FormatDecimals' => true));
if (!$conn) {
    fatalError("Could not connect.\n");
}

// Now try with setting number decimals to 3 then 2
testStmtOption($conn, $tableName, $values, $columns, 3, false);
testStmtOption($conn, $tableName, $values, $columns, 3, true);

testStmtOption($conn, $tableName, $values, $columns, 2, false);
testStmtOption($conn, $tableName, $values, $columns, 2, true);

// Test output parameters
testOutputParam($conn, $tableName, $values, $columns, $dataTypes);
testOutputParam($conn, $tableName, $values, $columns, $dataTypes, true);

dropTable($conn, $tableName);
sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done
