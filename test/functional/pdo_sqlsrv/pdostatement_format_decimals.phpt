--TEST--
Test statement attribute PDO::SQLSRV_ATTR_FORMAT_DECIMALS for decimal types
--DESCRIPTION--
Test statement attribute PDO::SQLSRV_ATTR_FORMAT_DECIMALS for decimal or 
money types (feature request issue 415), which are always fetched as strings
to preserve accuracy and precision, unlike other primitive numeric types, 
where there is an option to retrieve them as numbers.

This attribute expects an integer value from the range [0,38], the money or 
decimal types in the fetched result set can be formatted.

No effect on other operations like insertion or update.

1. By default, data will be returned with the original precision and scale
2. The data column original scale still takes precedence – for example, if the user
specifies 3 decimal digits for a column of decimal(5,2), the result still shows only 2
decimals to the right of the dot
3. After formatting, the missing leading zeroes will be padded
4. The underlying data will not be altered, but formatted results may likely be rounded
up (e.g. .2954 will be displayed as 0.30 if the user wants only two decimals)
5. Do not support output params
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function checkException($exception, $expected)
{
    if (strpos($exception->getMessage(), $expected) === false) {
        print_r($exception->getMessage());
        echo "\n";
    }
}

function testPdoAttribute($conn, $setAttr)
{
    // Expects exception because PDO::SQLSRV_ATTR_FORMAT_DECIMALS
    // is a statement level attribute
    try {
        $res = true;
        if ($setAttr) {
            $res = $conn->setAttribute(PDO::SQLSRV_ATTR_FORMAT_DECIMALS, 1);
        } else {
            $res = $conn->getAttribute(PDO::SQLSRV_ATTR_FORMAT_DECIMALS);
        }
        if ($res) {
            echo "setAttribute at PDO level should have failed!\n";
        }
    } catch (PdoException $e) {
        if ($setAttr) {
            $expected = 'The given attribute is only supported on the PDOStatement object.';
        } else {
            $expected = 'driver does not support that attribute';
        }

        checkException($e, $expected);
    }
}

function testErrorCases($conn)
{
    $query = "SELECT 0.0001";

    try {
        $options = array(PDO::SQLSRV_ATTR_FORMAT_DECIMALS => 0.9);
        $stmt = $conn->prepare($query, $options);
    } catch (PdoException $e) {
        $expected = 'Expected an integer to specify number of decimals to format the output values of decimal data types';
        checkException($e, $expected);
    }

    try {
        $options = array(PDO::SQLSRV_ATTR_FORMAT_DECIMALS => 100);
        $stmt = $conn->prepare($query, $options);
    } catch (PdoException $e) {
        $expected = 'For formatting decimal data values, 100 is out of range. Expected an integer from 0 to 38, inclusive.';
        checkException($e, $expected);
    }
}

function verifyMoneyValues($conn, $query, $values, $numDigits)
{
    $options = array(PDO::SQLSRV_ATTR_FORMAT_DECIMALS => $numDigits);
    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $results = $stmt->fetch(PDO::FETCH_NUM);

    trace("\nverifyMoneyValues:\n");
    for ($i = 0; $i < count($values); $i++) {
        $value = number_format($values[$i], $numDigits);
        trace("$results[$i], $value\n");

        if ($value !== $results[$i]) {
            echo "testMoneyTypes: Expected $value but got $results[$i]\n";
        }
    }
}

function testFloatTypes($conn)
{
    // This test with the float types of various number of bits, which are retrieved
    // as numbers by default. When fetched as strings, no formatting is done even with
    // the statement option FormatDecimals set
    $epsilon = 0.001;
    $values = array();
    for ($i = 0; $i < 5; $i++) {
        $n1 = rand(1, 100);
        $n2 = rand(1, 100);
        $neg = ($i % 2 == 0) ? -1 : 1;

        $n = $neg * $n1 / $n2;
        array_push($values, $n);
    }

    $query = "SELECT CONVERT(float(1), $values[0]),
                     CONVERT(float(12), $values[1]),
                     CONVERT(float(24), $values[2]),
                     CONVERT(float(36), $values[3]),
                     CONVERT(float(53), $values[4])";
    $stmt = $conn->query($query);
    $floats = $stmt->fetch(PDO::FETCH_NUM);
    unset($stmt);

    // Set PDO::SQLSRV_ATTR_FORMAT_DECIMALS to 2 should
    // have no effect on floating point numbers
    $numDigits = 2;
    $options = array(PDO::SQLSRV_ATTR_FORMAT_DECIMALS => $numDigits);
    $stmt = $conn->prepare($query, $options);

    // By default the floating point numbers are fetched as strings
    for ($i = 0; $i < 5; $i++) {
        $stmt->execute();
        $floatStr = $stmt->fetchColumn($i);

        $floatVal = floatVal($floats[$i]);
        $floatVal1 = floatval($floatStr);
        
        trace("testFloatTypes: $floatVal1, $floatVal\n");
        
        // Check if the numbers of decimal digits are the same
        // It is highly unlikely but not impossible
        $numbers = explode('.', $floatStr);
        $len = strlen($numbers[1]);
        if ($len == $numDigits && $floatVal1 != $floatVal) {
            echo "Expected $floatVal but $floatVal1 returned. \n";
        } else {
            $diff = abs($floatVal1 - $floatVal) / $floatVal;
            if ($diff > $epsilon) {
                echo "Expected $floatVal but $floatVal1 returned. \n";
            }
        }
    }
}

function testMoneyTypes($conn)
{
    // With money and smallmoney types, which are essentially decimal types
    // ODBC driver does not support Always Encrypted feature with money / smallmoney
    $values = array('24.559', '0', '-0.946', '0.2985', '-99.675', '79.995');
    $defaults = array('24.5590', '.0000', '-.9460', '.2985', '-99.6750', '79.9950');

    $query = "SELECT CONVERT(smallmoney, $values[0]),
                     CONVERT(money, $values[1]),
                     CONVERT(smallmoney, $values[2]),
                     CONVERT(money, $values[3]),
                     CONVERT(smallmoney, $values[4]),
                     CONVERT(money, $values[5])";

    $stmt = $conn->query($query);
    $results = $stmt->fetch(PDO::FETCH_NUM);
    for ($i = 0; $i < count($values); $i++) {
        if ($defaults[$i] !== $results[$i]) {
            echo "testMoneyTypes: Expected $defaults[$i] but got $results[$i]\n";
        }
    }
    unset($stmt);

    // Set PDO::SQLSRV_ATTR_FORMAT_DECIMALS to 0 then 2
    verifyMoneyValues($conn, $query, $values, 0);
    verifyMoneyValues($conn, $query, $values, 2);
}

function compareNumbers($actual, $input, $column, $fieldScale, $formatDecimal = -1)
{
    $matched = false;
    if ($actual === $input) {
        $matched = true;
        trace("Matched: $actual, $input\n");
    } else {
        // When $formatDecimal is negative, that means no formatting done
        // Otherwise, if $formatDecimal > $fieldScale, will show $fieldScale decimal digits
        if ($formatDecimal >= 0) {
            $numDecimals = ($formatDecimal > $fieldScale) ? $fieldScale : $formatDecimal;
            $expected = number_format($input, $numDecimals);
        } else {
            $expected = number_format($input, $fieldScale);
            if (abs($input) < 1) {
                // Since no formatting, the leading zero should not be there
                trace("Drop leading zero of $input--");
                $expected = str_replace('0.', '.', $expected);
            }
        }
        trace("With number_format: $actual, $expected\n");
        if ($actual === $expected) {
            $matched = true;
        } else {
            echo "For $column ($formatDecimal): expected $expected ($input) but the value is $actual\n";
        }
    }
    return $matched;
}

function testNoOption($conn, $tableName, $inputs, $columns)
{
    // Without the statement option, should return decimal values as they are
    $query = "SELECT * FROM $tableName";
    $stmt = $conn->query($query);

    // Compare values
    $results = $stmt->fetch(PDO::FETCH_NUM);
    trace("\ntestNoOption:\n");
    for ($i = 0; $i < count($inputs); $i++) {
        compareNumbers($results[$i], $inputs[$i], $columns[$i], $i);
    }
}

function testStmtOption($conn, $tableName, $inputs, $columns, $formatDecimal, $withBuffer)
{
    // Decimal values should return decimal digits based on the valid statement
    // option PDO::SQLSRV_ATTR_FORMAT_DECIMALS
    $query = "SELECT * FROM $tableName";
    if ($withBuffer){
        $options = array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
                         PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED,
                         PDO::SQLSRV_ATTR_FORMAT_DECIMALS => $formatDecimal);
    } else {
        $options = array(PDO::SQLSRV_ATTR_FORMAT_DECIMALS => $formatDecimal);
    }

    $size = count($inputs);
    $stmt = $conn->prepare($query, $options);

    // Fetch by getting one field at a time
    trace("\ntestStmtOption: $formatDecimal and buffered $withBuffer\n");
    for ($i = 0; $i < $size; $i++) {
        $stmt->execute();

        $stmt->bindColumn($columns[$i], $field);
        $result = $stmt->fetch(PDO::FETCH_BOUND);

        compareNumbers($field, $inputs[$i], $columns[$i], $i, $formatDecimal);
    }
}

function getOutputParam($conn, $storedProcName, $inputValue, $prec, $scale, $inout)
{
    $outString = '';
    $numDigits = 2;
    
    $outSql = getCallProcSqlPlaceholders($storedProcName, 1);
    
    $options = array(PDO::SQLSRV_ATTR_FORMAT_DECIMALS => $numDigits);
    $stmt = $conn->prepare($outSql, $options);
    
    $len = 1024;
    if ($inout) {
        $paramType = PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT;
        
        // For inout parameters the input type should match the output one
        $outString = '0.0';
    } else {
        $paramType = PDO::PARAM_STR;
    }

    $stmt->bindParam(1, $outString, $paramType, $len);
    $stmt->execute();

    // The output param value should be the same as the input value, 
    // unaffected by the statement attr PDO::SQLSRV_ATTR_FORMAT_DECIMALS,
    // unless ColumnEncryption is enabled, in which case the driver is able 
    // to derive the decimal type
    if (isAEConnected()) {
        trace("\ngetOutputParam ($inout) with AE:\n");
        $column = 'outputParamAE';
        compareNumbers($outString, $inputValue, $column, $scale, $numDigits);
    } else {
        trace("\ngetOutputParam ($inout) without AE:\n");
        $column = 'outputParam';
        compareNumbers($outString, $inputValue, $column, $scale);
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
        getOutputParam($conn, $storedProcName, $inputs[$i], $p, $i, $inout);
        
        dropProc($conn, $storedProcName);
    }
}

try {
    // This helper method sets PDO::ATTR_ERRMODE to PDO::ERRMODE_EXCEPTION
    $conn = connect();

    // Test some error conditions
    testPdoAttribute($conn, true);
    testPdoAttribute($conn, false);
    testErrorCases($conn);

    // First test with money types
    testMoneyTypes($conn);

    // Also test using regular floats
    testFloatTypes($conn);

    // Create the test table of decimal / numeric data columns
    $tableName = 'pdoFormatDecimals';

    $columns = array('c1', 'c2', 'c3', 'c4', 'c5', 'c6');
    $dataTypes = array('decimal(3,0)', 'decimal(4,1)', 'decimal(5,2)', 'numeric(6,3)', 'numeric(7,4)', 'numeric(8, 5)');

    $colMeta = array(new ColumnMeta($dataTypes[0], $columns[0]),
                     new ColumnMeta($dataTypes[1], $columns[1]),
                     new ColumnMeta($dataTypes[2], $columns[2]),
                     new ColumnMeta($dataTypes[3], $columns[3]),
                     new ColumnMeta($dataTypes[4], $columns[4]),
                     new ColumnMeta($dataTypes[5], $columns[5]));
    createTable($conn, $tableName, $colMeta);

    // Generate random input values based on precision and scale
    trace("\nGenerating random input values: \n");
    $values = array();
    $max = 1;
    for ($s = 0, $p = 3; $s < count($columns); $s++, $p++) {
        // First get a random number
        $n = rand(1, 6);
        $neg = ($n % 2 == 0) ? -1 : 1;

        // $n1 may or may not be negative
        $n1 = rand(0, 1000) * $neg;

        if ($s > 0) {
            $max *= 10;
            $n2 = rand(0, $max);
            $number = sprintf("%d.%d", $n1, $n2);
        } else {
            $number = sprintf("%d", $n1);
        }

        trace("$s: $number\n");
        array_push($values, $number);
    }

    $query = "INSERT INTO $tableName VALUES(?, ?, ?, ?, ?, ?)";
    $stmt = $conn->prepare($query);
    for ($i = 0; $i < count($columns); $i++) {
        $stmt->bindParam($i+1, $values[$i]);
    }
    $stmt->execute();

    testNoOption($conn, $tableName, $values, $columns, true);

    // Now try with setting number decimals to 3 then 2
    testStmtOption($conn, $tableName, $values, $columns, 3, false);
    testStmtOption($conn, $tableName, $values, $columns, 3, true);

    testStmtOption($conn, $tableName, $values, $columns, 2, false);
    testStmtOption($conn, $tableName, $values, $columns, 2, true);

    // Test output parameters
    testOutputParam($conn, $tableName, $values, $columns, $dataTypes);
    testOutputParam($conn, $tableName, $values, $columns, $dataTypes, true);

    dropTable($conn, $tableName); 
    echo "Done\n";

    unset($stmt);
    unset($conn);
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}
?>
--EXPECT--
Done
