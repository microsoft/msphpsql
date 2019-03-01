--TEST--
Test connection attributes for formatting money data (feature request issue 415)
--DESCRIPTION--
Test how money data in the fetched values can be formatted by using the connection attributes PDO::SQLSRV_ATTR_FORMAT_DECIMALS and PDO::SQLSRV_ATTR_DECIMAL_PLACES, the latter works only with integer values. No effect on other operations like insertion or update.

The PDO::SQLSRV_ATTR_DECIMAL_PLACES attribute only affects money/smallmoney fields. If its value is out of range, for example, it's negative or larger than the original scale, then its value will be ignored.

The underlying data will not be altered, but formatted results may likely be rounded up (e.g. .2954 will be displayed as 0.30 if the user wants only two decimals). For this reason, it is not recommended to use formatted money values as inputs to any calculation.

The corresponding statement attributes always override the inherited values from the connection object. Setting PDO::SQLSRV_ATTR_FORMAT_DECIMALS to false will automatically turn off any formatting of decimal data in the result set, ignoring PDO::SQLSRV_ATTR_DECIMAL_PLACES value.

By only setting PDO::SQLSRV_ATTR_FORMAT_DECIMALS to true will add the leading zeroes, if missing. 

Do not support output params.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function numberFormat($value, $numDecimals)
{
    return number_format($value, $numDecimals, '.', '');
}

function testFloatTypes($conn, $numDigits)
{
    // This test with the float types of various number of bits, which are retrieved
    // as numbers by default. When fetched as strings, no formatting is done,
    // because the connection attributes for formatting have no effect
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

    $stmt = $conn->query($query);
    $floats = $stmt->fetch(PDO::FETCH_NUM);
    unset($stmt);

    // By default the floating point numbers are fetched as strings
    $stmt = $conn->prepare($query);
    for ($i = 0; $i < 5; $i++) {
        $stmt->execute();
        $floatStr = $stmt->fetchColumn($i);

        $floatVal = floatVal($floats[$i]);
        $floatVal1 = floatval($floatStr);

        trace("testFloatTypes: $floatVal1, $floatVal\n");

        $diff = abs($floatVal1 - $floatVal) / $floatVal;
        if ($diff > $epsilon) {
            echo "$diff: Expected $floatVal but $floatVal1 returned. \n";
        }
    }
}

function verifyMoneyFormatting($conn, $query, $values, $format)
{
    if ($format) {
        // Set SQLSRV_ATTR_FORMAT_DECIMALS to true but
        // set SQLSRV_ATTR_DECIMAL_PLACES to a negative number
        // to override the inherited attribute
        $options = array(PDO::SQLSRV_ATTR_DECIMAL_PLACES => -1, PDO::SQLSRV_ATTR_FORMAT_DECIMALS => true);
    } else {
        // Set SQLSRV_ATTR_FORMAT_DECIMALS to false will
        // turn off any formatting -- overriding the inherited
        // attributes
        $options = array(PDO::SQLSRV_ATTR_FORMAT_DECIMALS => false);
    }

    $stmt = $conn->prepare($query, $options);
    $stmt->execute();
    $results = $stmt->fetch(PDO::FETCH_NUM);
    
    trace("\verifyMoneyFormatting:\n");
    for ($i = 0; $i < count($values); $i++) {
        // money types have a scale of 4
        $default = numberFormat($values[$i], 4);
        if (!$format) {
            // No formatting - should drop the leading zero, if exists
            if (abs($values[$i]) < 1) {
                $default = str_replace('0.', '.', $default);
            }
        } 
        if ($default !== $results[$i]) {
            echo "verifyMoneyFormatting ($format): Expected $default but got $results[$i]\n";
        }
    }
}

function verifyMoneyValues($conn, $numDigits, $query, $values, $override)
{
    if ($override) {
        $options = array(PDO::SQLSRV_ATTR_DECIMAL_PLACES => $numDigits);
        $stmt = $conn->prepare($query, $options);
    } else {
        // Use the connection defaults
        $stmt = $conn->prepare($query);
    }
    $stmt->execute();
    $results = $stmt->fetch(PDO::FETCH_NUM);

    trace("\nverifyMoneyValues:\n");
    for ($i = 0; $i < count($values); $i++) {
        $value = numberFormat($values[$i], $numDigits);
        trace("$results[$i], $value\n");

        if ($value !== $results[$i]) {
            echo "testMoneyTypes ($override, $numDigits): Expected $value but got $results[$i]\n";
        }
    }
}

function testMoneyTypes($conn, $numDigits)
{
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
    // decimal places
    verifyMoneyValues($conn, 0, $query, $values, true);
    
    // Set Formatting attribute to true then false
    verifyMoneyFormatting($conn, $query, $values, true);
    verifyMoneyFormatting($conn, $query, $values, false);
}

function connGetAttributes($conn, $numDigits) 
{
    $format = $conn->getAttribute(PDO::SQLSRV_ATTR_FORMAT_DECIMALS);
    if ($format !== true) {
        echo "The returned value of SQLSRV_ATTR_FORMAT_DECIMALS, $format, is wrong\n";
        
        return false;
    }
    
    $digits = $conn->getAttribute(PDO::SQLSRV_ATTR_DECIMAL_PLACES);
    if ($digits != $numDigits) {
        echo "The returned value of SQLSRV_ATTR_DECIMAL_PLACES, $digits, is wrong\n";

        return false;
    }
    
    return true;
}

function connectWithAttrs($numDigits)
{
    $attr = array(PDO::SQLSRV_ATTR_FORMAT_DECIMALS => true,
                  PDO::SQLSRV_ATTR_DECIMAL_PLACES => $numDigits);

    // This helper method sets PDO::ATTR_ERRMODE to PDO::ERRMODE_EXCEPTION
    $conn = connect('', $attr);

    if (connGetAttributes($conn, $numDigits)) {
        // First test with money types
        testMoneyTypes($conn, $numDigits);

        // Also test using regular floats
        testFloatTypes($conn, $numDigits);
    }
    unset($conn);
}

function connectSetAttrs($numDigits)
{
    // This helper method sets PDO::ATTR_ERRMODE to PDO::ERRMODE_EXCEPTION
    $conn = connect();
    $conn->setAttribute(PDO::SQLSRV_ATTR_FORMAT_DECIMALS, true);
    $conn->setAttribute(PDO::SQLSRV_ATTR_DECIMAL_PLACES, $numDigits);

    if (connGetAttributes($conn, $numDigits)) {
        // First test with money types
        testMoneyTypes($conn, $numDigits);

        // Also test using regular floats
        testFloatTypes($conn, $numDigits);
    }

    unset($conn);
}

try {
    connectWithAttrs(2);
    connectSetAttrs(3);

    echo "Done\n";

    unset($conn);
} catch (PdoException $e) {
    echo $e->getMessage() . PHP_EOL;
}
?>
--EXPECT--
Done
