--TEST--
Test connection and statement attributes for formatting decimal and numeric data (feature request issue 415)
--DESCRIPTION--
Test the attributes PDO::SQLSRV_ATTR_FORMAT_DECIMALS and PDO::SQLSRV_ATTR_DECIMAL_PLACES, the latter affects money types only, not decimal or numeric types (feature request issue 415).
Money, decimal or numeric types are always fetched as strings to preserve accuracy and precision, unlike other primitive numeric types, where there is an option to retrieve them as numbers.

Setting PDO::SQLSRV_ATTR_FORMAT_DECIMALS to false will turn off all formatting, regardless of PDO::SQLSRV_ATTR_DECIMAL_PLACES value. Also, any negative PDO::SQLSRV_ATTR_DECIMAL_PLACES value will be ignored. Likewise, since money or smallmoney fields have scale 4, if PDO::SQLSRV_ATTR_DECIMAL_PLACES value is larger than 4, it will be ignored as well.

1. By default, data will be returned with the original precision and scale
2. Set PDO::SQLSRV_ATTR_FORMAT_DECIMALS to true to add the leading zeroes to money and decimal types, if missing.
3. No support for output params

The attributes PDO::SQLSRV_ATTR_FORMAT_DECIMALS and PDO::SQLSRV_ATTR_DECIMAL_PLACES will only format the 
fetched results and have no effect on other operations like insertion or update.
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

function testErrorCases($conn)
{
    $expected = 'Expected an integer to specify number of decimals to format the output values of decimal data types';
    $query = "SELECT 0.0001";

    try {
        $conn->setAttribute(PDO::SQLSRV_ATTR_FORMAT_DECIMALS, 0);
        $format = $conn->getAttribute(PDO::SQLSRV_ATTR_FORMAT_DECIMALS);
        if ($format !== false) {
            echo 'The value of PDO::SQLSRV_ATTR_FORMAT_DECIMALS should be false\n';
            var_dump($format);
        }
        
        $conn->setAttribute(PDO::SQLSRV_ATTR_DECIMAL_PLACES, 1.5);
    } catch (PdoException $e) {
        checkException($e, $expected);
    }

    try {
        $options = array(PDO::SQLSRV_ATTR_DECIMAL_PLACES => 0.9);
        $stmt = $conn->prepare($query, $options);
    } catch (PdoException $e) {
        checkException($e, $expected);
    }

    try {
        $options = array(PDO::SQLSRV_ATTR_DECIMAL_PLACES => true);
        $stmt = $conn->prepare($query, $options);
    } catch (PdoException $e) {
        checkException($e, $expected);
    }
}

function compareNumbers($actual, $input, $column, $fieldScale, $format = true)
{
    $matched = false;
    if ($actual === $input) {
        $matched = true;
        trace("Matched: $actual, $input\n");
    } else {
        // if no formatting, there will be no leading zero
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

function testNoOption($conn, $tableName, $inputs, $columns)
{
    // Without the statement option, should return decimal values as they are
    $query = "SELECT * FROM $tableName";
    $stmt = $conn->query($query);

    // Compare values
    $results = $stmt->fetch(PDO::FETCH_NUM);
    trace("\ntestNoOption:\n");
    for ($i = 0; $i < count($inputs); $i++) {
        compareNumbers($results[$i], $inputs[$i], $columns[$i], $i, false);
    }
}

function testStmtOption($conn, $tableName, $inputs, $columns, $decimalPlaces, $withBuffer)
{
    // Decimal values should NOT be affected by the statement
    // attribute PDO::SQLSRV_ATTR_FORMAT_DECIMALS
    $query = "SELECT * FROM $tableName";
    if ($withBuffer){
        $options = array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL,
                         PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED,
                         PDO::SQLSRV_ATTR_DECIMAL_PLACES => $decimalPlaces);
    } else {
        $options = array(PDO::SQLSRV_ATTR_DECIMAL_PLACES => $decimalPlaces);
    }

    $size = count($inputs);
    $stmt = $conn->prepare($query, $options);

    // Fetch by getting one field at a time
    trace("\ntestStmtOption: $decimalPlaces and buffered $withBuffer\n");
    for ($i = 0; $i < $size; $i++) {
        $stmt->execute();

        $stmt->bindColumn($columns[$i], $field);
        $result = $stmt->fetch(PDO::FETCH_BOUND);

        compareNumbers($field, $inputs[$i], $columns[$i], $i);
    }
}

function getOutputParam($conn, $storedProcName, $inputValue, $prec, $scale, $inout)
{
    $outString = '';
    $numDigits = 2;
    
    $outSql = getCallProcSqlPlaceholders($storedProcName, 1);
    
    $options = array(PDO::SQLSRV_ATTR_DECIMAL_PLACES => $numDigits);
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

    // The output param value should be unaffected by the attr 
    // PDO::SQLSRV_ATTR_DECIMAL_PLACES. Without ColumnEncryption, the 
    // output param is treated as a regular string (not a decimal), so
    // no missing leading zeroes.
    // If ColumnEncryption is enabled, in which case the driver is able 
    // to derive the decimal type, leading zero will be added if missing.
    if (isAEConnected()) {
        trace("\ngetOutputParam ($inout) with AE:\n");
        $column = 'outputParamAE';
        compareNumbers($outString, $inputValue, $column, $scale);
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
    testErrorCases($conn);

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

        // $n1, a tiny number, which may or may not be negative,
        $n1 = rand(0, 5) * $neg;

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

    // Turn on formatting, which only add leading zeroes, if missing
    // decimal and numeric types should be unaffected by 
    // PDO::SQLSRV_ATTR_DECIMAL_PLACES whatsoever
    $conn->setAttribute(PDO::SQLSRV_ATTR_FORMAT_DECIMALS, true);

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
