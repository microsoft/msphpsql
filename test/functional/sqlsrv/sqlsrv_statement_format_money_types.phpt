--TEST--
Test the options for formatting money data (feature request issue 415)
--DESCRIPTION--
Test how money data in the fetched values can be formatted by using the connection
option FormatDecimals and DecimalPlaces, the latter works only with integer
values. No effect on other operations like insertion or update.

The option DecimalPlaces only affects money/smallmoney fields. If its value is out of range, for example, it's negative or larger than the original scale, then its value will be ignored.

The underlying data will not be altered, but formatted results may likely be rounded up (e.g. .2954 will be displayed as 0.30 if the user wants only two decimals). For this reason, it is not recommended to use formatted money values as inputs to any calculation.

The corresponding statement options always override the inherited values from the connection object. Setting FormatDecimals to false will automatically turn off any formatting of decimal data in the result set, ignoring DecimalPlaces value.

By only setting FormatDecimals to true will add the leading zeroes, if missing. For output params, missing zeroes will be added if either SQLSRV_SQLTYPE_MONEY or SQLSRV_SQLTYPE_SMALLMONEY is set as the SQLSRV SQL Type.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function numberFormat($value, $numDecimals)
{
    return number_format($value, $numDecimals, '.', '');
}

function testFloatTypes($conn)
{
    global $numDigits;      // inherited from connection option

    // This test with the float types of various number of bits, which are retrieved
    // as numbers by default. When fetched as strings, no formatting is done,
    // because connection options for formatting have no effect
    $epsilon = 0.001;
    $nColumns = 5;

    $values = array();
    for ($i = 0; $i < $nColumns; $i++) {
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

    $stmt = sqlsrv_query($conn, $query);
    $floats = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    if (!$floats) {
        echo "testFloatTypes: sqlsrv_fetch_array failed\n";
    }

    // The number of decimals in each of the results will vary
    $stmt = sqlsrv_query($conn, $query);
    if (sqlsrv_fetch($stmt)) {
        for ($i = 0; $i < count($values); $i++) {
            $floatStr = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
            $floatVal = floatval($floatStr);
            $diff = abs($floatVal - $floats[$i]) / $floats[$i];
            if ($diff > $epsilon) {
                echo "$diff: Expected $floats[$i] but returned ";
                var_dump($floatVal);
            }
        }
    } else {
        echo "testFloatTypes: sqlsrv_fetch failed\n";
    }
}

function verifyMoneyValues($conn, $numDigits, $query, $values, $override)
{
    if ($override) {
        $options = array('DecimalPlaces' => $numDigits);
        $stmt = sqlsrv_query($conn, $query, array(), $options);
    } else {
        $stmt = sqlsrv_query($conn, $query);
    }

    $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    trace("\nverifyMoneyValues:\n");
    for ($i = 0; $i < count($values); $i++) {
        $value = numberFormat($values[$i], $numDigits);
        trace("$results[$i], $value\n");

        if ($value !== $results[$i]) {
            echo "verifyMoneyValues ($override, $numDigits): Expected $value but got $results[$i]\n";
        }
    }
}

function verifyMoneyFormatting($conn, $query, $values, $format)
{
    if ($format) {
        // Set FormatDecimals to true to turn on formatting, but setting
        // DecimalPlaces to a negative number, which will be ignored.
        $nDigits = -1;
        $options = array('FormatDecimals' => true, 'DecimalPlaces' => $nDigits);
        $stmt = sqlsrv_query($conn, $query, array(), $options);
    } else {
        // Set FormatDecimals to false to turn off formatting.
        // This should override the inherited connection
        // options, and by default, money and smallmoney types
        // have scale of 4 digits
        $options = array('FormatDecimals' => false);
        $stmt = sqlsrv_query($conn, $query, array(), $options);
    }
    $results = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);

    for ($i = 0; $i < count($values); $i++) {
        $default = numberFormat($values[$i], 4);
        if (!$format) {
            // No formatting - should drop the leading zero, if exists
            if (abs($values[$i]) < 1) {
                $default = str_replace('0.', '.', $default);
            }
        }
        if ($default !== $results[$i]) {
            echo "verifyMoneyFormatting ($format): Expected default $default but got $results[$i]\n";
        }
    }
}

function getOutputParam($conn, $spProcName, $input, $money, $inout)
{
    $outString = '0.0';
    $dir = ($inout) ? SQLSRV_PARAM_INOUT : SQLSRV_PARAM_OUT;
    $sqlType = ($money) ? SQLSRV_SQLTYPE_MONEY : SQLSRV_SQLTYPE_SMALLMONEY;
    
    $outSql = AE\getCallProcSqlPlaceholders($spProcName, 1);
    $stmt = sqlsrv_prepare($conn, $outSql,
                            array(array(&$outString, $dir, null, $sqlType)));
    if (!$stmt) {
        fatalError("getOutputParam: failed when preparing to call $spProcName");
    }
    if (!sqlsrv_execute($stmt)) {
        fatalError("getOutputParam: failed to execute procedure $spProcName");
    }

    // FormatDecimals only add leading zeroes, but do
    // not support controlling decimal places, so 
    // use scale 4 for money/smallmoney types
    $expected = numberFormat($input, 4);
    trace("getOutputParam result is $outString and expected $expected\n");

    if ($outString !== $expected) {
        echo "getOutputParam ($inout): Expected $expected but got $outString\n";
        var_dump($expected); 
        var_dump($outString);
    }
}

function testOutputParam($conn)
{
    // Create a table for testing output param
    $tableName = 'sqlsrvMoneyFormats';
    $values = array(0.12345, 0.34567);
    $query = "SELECT CONVERT(smallmoney, $values[0]) AS m1,
                     CONVERT(money, $values[1]) AS m2
                     INTO $tableName";

    $stmt = sqlsrv_query($conn, $query);
    for ($i = 0; $i < 2; $i++) {
        // Create the stored procedure first
        $storedProcName = "spMoneyFormats" . $i;
        $dataType = ($i == 0) ? 'smallmoney' : 'money';
        $procArgs = "@col $dataType OUTPUT";
        $column = 'm' . ($i + 1);
        $procCode = "SELECT @col = $column FROM $tableName";
        createProc($conn, $storedProcName, $procArgs, $procCode);
        
        getOutputParam($conn, $storedProcName, $values[$i], $i, false);
        getOutputParam($conn, $storedProcName, $values[$i], $i, true);
        
        dropProc($conn, $storedProcName);
    }
    
    dropTable($conn, $tableName);
}

function testMoneyTypes($conn)
{
    global $numDigits;      // inherited from connection option

    // With money and smallmoney types, which are essentially decimal types
    // As of today, ODBC driver does not support Always Encrypted feature with money / smallmoney
    $values = array();
    $nColumns = 6;
    for ($i = 0; $i < $nColumns; $i++) {
        // First get a random number
        $n = rand(0, 10);
        $neg = ($n % 2 == 0) ? -1 : 1;

        // $n1 may or may not be negative
        $max = 10;
        $n1 = rand(0, $max) * $neg;
        $n2 = rand(1, $max * 1000);

        $number = sprintf("%d.%d", $n1, $n2);
        array_push($values, $number);
    }

    $query = "SELECT CONVERT(smallmoney, $values[0]),
                     CONVERT(money, $values[1]),
                     CONVERT(smallmoney, $values[2]),
                     CONVERT(money, $values[3]),
                     CONVERT(smallmoney, $values[4]),
                     CONVERT(money, $values[5])";

    // Do not override the connection attributes
    verifyMoneyValues($conn, $numDigits, $query, $values, false);
    // Next, override statement attribute to set number of
    // decimal places to 0
    verifyMoneyValues($conn, 0, $query, $values, true);

    // Set Formatting attribute to true then false
    verifyMoneyFormatting($conn, $query, $values, true);
    verifyMoneyFormatting($conn, $query, $values, false);
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

$numDigits = 2;

$conn = AE\connect(array('FormatDecimals' => true, 'DecimalPlaces' => $numDigits));
if (!$conn) {
    fatalError("Could not connect.\n");
}

// First to test if leading zero is added
testMoneyTypes($conn);

// Also test using regular floats
testFloatTypes($conn);

// Test output params
testOutputParam($conn);

sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done
