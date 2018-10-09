--TEST--
Test how decimal data output values can be formatted (feature request issue 415)
--DESCRIPTION--
Test how numeric and decimal data output values can be formatted by using the
statement option FormatDecimals, which expects an integer value from the range [0,38],
affecting only the money / decimal types in the fetched result set because they are
always strings to preserve accuracy and precision, unlike other primitive numeric 
types that can be retrieved as numbers.

No effect on other operations like insertion or update.

1. By default, data will be returned with the original precision and scale 
2. The data column original scale still takes precedence – for example, if the user
specifies 3 decimal digits for a column of decimal(5,2), the result still shows only 2
decimals to the right of the dot
3. After formatting, the missing leading zeroes will be padded 
4. The underlying data will not be altered, but formatted results may likely be rounded 
up (e.g. .2954 will be displayed as 0.30 if the user wants only two decimals)
5. For output params use SQLSRV_SQLTYPE_DECIMAL with the correct precision and scale
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function compareNumbers($actual, $input, $column, $fieldScale, $formatDecimal = 0)
{
    $matched = false;
    if ($actual === $input) {
        $matched = true;
    } else {
        // When $formatDecimal is negative, that means no formatting done
        // Otherwise, if $formatDecimal > $fieldScale, will show $fieldScale decimal digits
        if ($formatDecimal > 0) {
            $numDecimals = ($formatDecimal > $fieldScale) ? $fieldScale : $formatDecimal;
        } else {
            $numDecimals = $fieldScale;
        }
        $expected = number_format($input, $numDecimals);
        if ($actual === $expected) {
            $matched = true;
        } else {
            echo "For $column: expected $expected but the value is $actual\n";
        }
    }
    return $matched;
}

function testErrorCases($conn)
{
    $query = "SELECT 0.0001";
    
    $options = array('FormatDecimals' => true);
    $stmt = sqlsrv_query($conn, $query, array(), $options);
    if ($stmt) {
        fatalError("Case 1: expected query to fail!!");
    } else {
        $error = sqlsrv_errors()[0]['message'];
        $message = 'Expected an integer to specify number of decimals to format the output values of decimal data types.';
        
        if (strpos($error, $message) === false) {
            print_r(sqlsrv_errors());
        }
    }

    $options = array('FormatDecimals' => -1);
    $stmt = sqlsrv_query($conn, $query, array(), $options);
    if ($stmt) {
        fatalError("Case 2: expected query to fail!!");
    } else {
        $error = sqlsrv_errors()[0]['message'];
        $message = 'For formatting decimal data values, -1 is out of range. Expected an integer from 0 to 38, inclusive.';
        
        if (strpos($error, $message) === false) {
            print_r(sqlsrv_errors());
        }
    }
}

function testMoneyTypes($conn)
{
    // With money and smallmoney types, which are essentially decimal types 
    // ODBC driver does not support Always Encrypted feature with money / smallmoney 
    $values = array('1.9954', '0', '-0.5', '0.2954', '9.6789', '99.991');
    $defaults = array('1.9954', '.0000', '-.5000', '.2954', '9.6789', '99.9910');
    
    $query = "SELECT CONVERT(smallmoney, $values[0]), 
                     CONVERT(money, $values[1]), 
                     CONVERT(smallmoney, $values[2]),
                     CONVERT(money, $values[3]),
                     CONVERT(smallmoney, $values[4]),
                     CONVERT(money, $values[5])";

    $stmt = sqlsrv_query($conn, $query);
    $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    for ($i = 0; $i < count($values); $i++) {
        if ($defaults[$i] !== $results[$i]) {
            echo "testMoneyTypes: Expected default $defaults[$i] but got $results[$i]\n";
        }
    }
    
    // Set FormatDecimals to 0 decimal digits
    $numDigits = 0;
    $options = array('FormatDecimals' => $numDigits);
    $stmt = sqlsrv_query($conn, $query, array(), $options);
    $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    for ($i = 0; $i < count($values); $i++) {
        $value = number_format($values[$i], $numDigits);
        if ($value !== $results[$i]) {
            echo "testMoneyTypes: Expected $value but got $results[$i]\n";
        }
    }

    // Set FormatDecimals to 2 decimal digits
    $numDigits = 2;
    $options = array('FormatDecimals' => $numDigits);
    $stmt = sqlsrv_query($conn, $query, array(), $options);
    $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    for ($i = 0; $i < count($values); $i++) {
        $value = number_format($values[$i], $numDigits);
        if ($value !== $results[$i]) {
            echo "testMoneyTypes: Expected $value but got $results[$i]\n";
        }
    }
}

function testNoOption($conn, $tableName, $inputs, $columns, $exec)
{
    // Without the statement option, should return decimal values as they are
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
        compareNumbers($results[$i], $inputs[$i], $columns[$i], $i);
    }
}

function testStmtOption($conn, $tableName, $inputs, $columns, $formatDecimal, $withBuffer)
{
    // Decimal values should return decimal digits based on the valid statement 
    // option FormatDecimals
    $query = "SELECT * FROM $tableName";
    if ($withBuffer){
        $options = array('Scrollable' => 'buffered', 'FormatDecimals' => $formatDecimal);
    } else {
        $options = array('FormatDecimals' => $formatDecimal);
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
        compareNumbers($field, $inputs[$i], $columns[$i], $i, $formatDecimal);
    }
}

function getOutputParam($conn, $storedProcName, $inputValue, $prec, $scale)
{
    $outString = '';
    $numDigits = 2;
    
    // Derive the sql type SQLSRV_SQLTYPE_DECIMAL($prec, $scale)
    $sqlType = call_user_func('SQLSRV_SQLTYPE_DECIMAL', $prec, $scale);

    $outSql = AE\getCallProcSqlPlaceholders($storedProcName, 1);
    $stmt = sqlsrv_prepare($conn, $outSql, 
                            array(array(&$outString, SQLSRV_PARAM_OUT, null, $sqlType)), 
                            array('FormatDecimals' => $numDigits));
    if (!$stmt) {
        fatalError("getOutputParam: failed when preparing to call $storedProcName");
    }
    if (!sqlsrv_execute($stmt)) {
        fatalError("getOutputParam: failed to execute procedure $storedProcName");
    }
    
    compareNumbers($outString, $inputValue, 'outputParam', $scale, $numDigits);
}

function testOutputParam($conn, $tableName, $inputs, $columns, $dataTypes)
{
    for ($i = 0, $p = 3; $i < count($columns); $i++, $p++) {
        // Create the stored procedure first
        $storedProcName = "spFormatDecimals" . $i;
        $procArgs = "@col $dataTypes[$i] OUTPUT";
        $procCode = "SELECT @col = $columns[$i] FROM $tableName";
        createProc($conn, $storedProcName, $procArgs, $procCode);

        // Call stored procedure to retrieve output param
        getOutputParam($conn, $storedProcName, $inputs[$i], $p, $i);
        
        dropProc($conn, $storedProcName);
    }
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

$conn = AE\connect();
if (!$conn) {
    fatalError("Could not connect.\n");
}

// First to test if leading zero is added
testMoneyTypes($conn);

// Then test error conditions
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
for ($s = 0, $p = 3; $s < count($columns); $s++, $p++) {
    // First get a random number
    $n = rand(0, 10);
    $neg = ($n % 2 == 0) ? -1 : 1;
    
    // $n1 may or may not be negative
    $max1 = 1000;
    $n1 = rand(0, $max1) * $neg;
    
    if ($s > 0) {
        $max2 = pow(10, $s);
        $n2 = rand(0, $max2);
        $number = sprintf("%d.%d", $n1, $n2);
    } else {
        $number = sprintf("%d", $n1);
    }
    
    array_push($values, $number);
}

if (traceMode()) {
    var_dump($values);
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
    fatalError("Failed to insert data.\n");
}
sqlsrv_free_stmt($stmt);

testNoOption($conn, $tableName, $values, $columns, true);
testNoOption($conn, $tableName, $values, $columns, false);

// Now try with setting number decimals to 3 then 2
testStmtOption($conn, $tableName, $values, $columns, 3, false);
testStmtOption($conn, $tableName, $values, $columns, 3, true);

testStmtOption($conn, $tableName, $values, $columns, 2, false);
testStmtOption($conn, $tableName, $values, $columns, 2, true);

// Test output parameters
testOutputParam($conn, $tableName, $values, $columns, $dataTypes);

dropTable($conn, $tableName); 
sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done
