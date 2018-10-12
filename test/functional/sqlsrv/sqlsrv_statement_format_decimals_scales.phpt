--TEST--
Test various precisions of formatting decimal data output values (feature request issue 415)
--DESCRIPTION--
In SQL Server, the maximum allowed precision is 38. The scale can range from 0 up to the
defined precision. Generate a long numeric string and get rid of the last digit to make it a
39-digit-string. Then replace one digit at a time with a dot '.' to make it a decimal
input string for testing with various scales.
For example, 
string(39) ".23456789012345678901234567890123456789"
string(39) "1.3456789012345678901234567890123456789"
string(39) "12.456789012345678901234567890123456789"
string(39) "123.56789012345678901234567890123456789"
string(39) "1234.6789012345678901234567890123456789"
string(39) "12345.789012345678901234567890123456789"
... ...
string(39) "1234567890123456789012345678901234.6789"
string(39) "12345678901234567890123456789012345.789"
string(39) "123456789012345678901234567890123456.89"
string(39) "1234567890123456789012345678901234567.9"
string(38) "12345678901234567890123456789012345678"

Note: PHP number_format() will not be used for verification in this test 
because the function starts losing accuracy with large number of precisions / scales.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$prec = 38;
$dot = '.';

function createTestTable($conn)
{
    global $prec;
    
    // Create the test table of one decimal column
    $tableName = "sqlsrvFormatDecimalScales";
    $colMeta = array();
    
    $max = $prec + 1;
    for ($i = 0; $i < $max; $i++) {
        $scale = $prec - $i;

        $column = "col_$scale";
        $dataType = "decimal($prec, $scale)";
        
        array_push($colMeta, new AE\ColumnMeta($dataType, $column));
    }
    AE\createTable($conn, $tableName, $colMeta);

    return $tableName;
}

function insertTestData($conn, $tableName)
{
    global $prec, $dot;
    
    $temp = str_repeat('1234567890', 4);
    $digits = substr($temp, 0, $prec + 1);
    
    $inputData = array();
    $max = $prec + 1;
    
    // Generate input strings - replace the $i-th digit with a dot '.' 
    for ($i = 0; $i < $max; $i++) {
        $d = $digits[$i];
        $digits[$i] = $dot;
        
        if ($i == $prec) {
            $digits = substr($temp, 0, $prec);
        } 

        $scale = $prec - $i;

        $column = "col_$scale";
        $inputData = array_merge($inputData, array($column => $digits));
        
        // Restore the $i-th digit with its original digit
        $digits[$i] = $d;
    }
    
    $stmt = AE\insertRow($conn, $tableName, $inputData);
    if (!$stmt) {
        fatalError("Failed to insert data\n");
    }
    sqlsrv_free_stmt($stmt);
    
    return $inputData;
}

function verifyNoDecimals($value, $input, $round)
{
    global $prec, $dot;
    
    // Use PHP explode() to separate the input string into an array
    $parts = explode($dot, $input);
    $len = strlen($parts[0]);
    if ($len == 0) {
        // The original input string is missing a leading zero
        $parts[0] = '0';
    }

    // No need to worry about carry over for the input data of this test
    // Check the first digit of $parts[1] 
    if ($len < $prec) {
        // Only need to round up when $len < $prec
        $ch = $parts[1][0];

        // Round the last digit of $parts[0] if $ch is '5' or above
        if ($ch >= '5') {
            $len = strlen($parts[0]);
            $parts[0][$len-1] = $parts[0][$len-1] + 1 + '0';
        }
    } 

    // No decimal digits left in the expected string
    $expected = $parts[0];
    if ($value !== $expected) {
        echo "Round $round scale 0: expected $expected but returned $value\n";
    }
}

function verifyWithDecimals($value, $input, $round, $scale) 
{
    global $dot;
    
    // Use PHP explode() to separate the input string into an array
    $parts = explode($dot, $input);
    if (strlen($parts[0]) == 0) {
        // The original input string is missing a leading zero
        $parts[0] = '0';
    }

    // No need to worry about carry over for the input data of this test
    // Check the digit at the position $scale of $parts[1] 
    $len = strlen($parts[1]);
    if ($scale < $len) {
        // Only need to round up when $scale < $len
        $ch = $parts[1][$scale];
        
        // Round the previous digit if $ch is '5' or above
        if ($ch >= '5') {
            $parts[1][$scale-1] = $parts[1][$scale-1] + 1 + '0';
        }
    }
    
    // Use substr() to get up to $scale
    $parts[1] = substr($parts[1], 0, $scale);
    
    // Join the array elements together
    $expected = implode($dot, $parts);
    if ($value !== $expected) {
        echo "Round $round scale $scale: expected $expected but returned $value\n";
    }
}

/**** 
The function testVariousScales() will fetch one column at a time, using scale from  
0 up to the maximum scale allowed for that column type.

For example, for column of type decimal(38,4), the input string is 
1234567890123456789012345678901234.6789

When fetching data, using scale from 0 to 4, the following values are expected to return:
1234567890123456789012345678901235
1234567890123456789012345678901234.7
1234567890123456789012345678901234.68
1234567890123456789012345678901234.679
1234567890123456789012345678901234.6789

For example, for column of type decimal(38,6), the input string is 
12345678901234567890123456789012.456789

When fetching data, using scale from 0 to 6, the following values are expected to return:
12345678901234567890123456789012
12345678901234567890123456789012.5
12345678901234567890123456789012.46
12345678901234567890123456789012.457
12345678901234567890123456789012.4568
12345678901234567890123456789012.45679
12345678901234567890123456789012.456789

etc.
****/
function testVariousScales($conn, $tableName, $inputData) 
{
    global $prec;
    $max = $prec + 1;
    
    for ($i = 0; $i < $max; $i++) {
        $scale = $prec - $i;
        $column = "col_$scale";

        $query = "SELECT $column as col1 FROM $tableName";
        $input = $inputData[$column];
        
        // Default case: the fetched value should be the same as the corresponding input
        $stmt = sqlsrv_query($conn, $query);
        if (!$stmt) {
            fatalError("In testVariousScales: failed in default case\n");
        }
        if ($obj = sqlsrv_fetch_object($stmt)) {
            trace("\n$obj->col1\n");
            if ($obj->col1 !== $input) {
                echo "default case: expected $input but returned $obj->col1\n";
            }
        } else {
            fatalError("In testVariousScales: sqlsrv_fetch_object failed\n");
        }
        
        // Next, format how many decimal digits to be displayed
        $query = "SELECT $column FROM $tableName";
        for ($j = 0; $j <= $scale; $j++) {
            $options = array('FormatDecimals' => $j);
            $stmt = sqlsrv_query($conn, $query, array(), $options);

            if (sqlsrv_fetch($stmt)) {
                $value = sqlsrv_get_field($stmt, 0);
                trace("$value\n");

                if ($j == 0) {
                    verifyNoDecimals($value, $input, $i);
                } else {
                    verifyWithDecimals($value, $input, $i, $j);
                }
            } else {
                fatalError("Round $i scale $j: sqlsrv_fetch failed\n");
            }
        }
    }
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

$conn = AE\connect();
if (!$conn) {
    fatalError("Could not connect.\n");
}

$tableName = createTestTable($conn);
$inputData = insertTestData($conn, $tableName);
testVariousScales($conn, $tableName, $inputData);

dropTable($conn, $tableName); 

sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done