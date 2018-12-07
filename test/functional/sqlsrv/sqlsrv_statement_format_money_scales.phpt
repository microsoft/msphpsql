--TEST--
Test various decimal places of money values (feature request issue 415)
--DESCRIPTION--
In SQL Server, the maximum precision of money type is 19 with scale 4. Generate a long numeric string and get rid of the last digit to make it a 15-digit-string. Then replace one digit at a time with a dot '.' to make it a decimal input string for testing.

For example, 
string(15) ".23456789098765"
string(15) "1.3456789098765"
string(15) "12.456789098765"
string(15) "123.56789098765"
string(15) "1234.6789098765"
...
string(15) "1234567890987.5"
string(15) "12345678909876."

The inserted money data will be 
0.2346
1.3457
12.4568
123.5679
1234.6789
...
1234567890987.5000
12345678909876.0000

--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$prec = 19;
$scale = 4;
$dot = '.';

function createTestTable($conn)
{
    global $prec, $scale;
    
    // Create the test table 
    $tableName = "sqlsrvFormatMoneyScales";
    $colMeta = array();
    
    $max = $prec - $scale; 
    for ($i = 0; $i < $max; $i++) {
        $column = "col_$i";
        $dataType = 'money';
        
        array_push($colMeta, new AE\ColumnMeta($dataType, $column, null, true, true));
    }
    AE\createTable($conn, $tableName, $colMeta);

    return $tableName;
}

function insertTestData($conn, $tableName)
{
    global $prec, $scale, $dot;
    
    $digits = substr('1234567890987654321', 0, $prec - $scale);
    
    $inputData = array();
    $max = $prec - $scale; 
    
    // Generate input strings - replace the $i-th digit with a dot '.' 
    for ($i = 0; $i < $max; $i++) {
        $d = $digits[$i];
        $digits[$i] = $dot;
        
        $column = "col_$i";
        $inputData = array_merge($inputData, array($column => $digits));
        trace($digits);
        
        // Restore the $i-th digit with its original digit
        $digits[$i] = $d;
    }
    
    $stmt = AE\insertRow($conn, $tableName, $inputData);
    if (!$stmt) {
        fatalError("Failed to insert data\n");
    }
    sqlsrv_free_stmt($stmt);
}

function numberFormat($value, $numDecimals)
{
    return number_format($value, $numDecimals, '.', '');
}

/**** 
The function testVariousScales() will fetch one column at a time, using scale from 0 up to 4 allowed for that column type.

For example, if the input string is 
1234567890.2345

When fetching data, using scale from 0 to 4, the following values are expected to return:
1234567890
1234567890.2
1234567890.23
1234567890.235
1234567890.2345
****/
function testVariousScales($conn, $tableName) 
{
    global $prec, $scale;
    $max = $prec - $scale; 
    
    for ($i = 0; $i < $max; $i++) {
        $column = "col_$i";

        $query = "SELECT $column as col1 FROM $tableName";
        // Default case: no formatting
        $stmt = sqlsrv_query($conn, $query);
        if (!$stmt) {
            fatalError("In testVariousScales: failed in default case\n");
        }
        if ($obj = sqlsrv_fetch_object($stmt)) {
            trace("\n$obj->col1\n");
            $input = $obj->col1;
        } else {
            fatalError("In testVariousScales: sqlsrv_fetch_object failed\n");
        }
        
        // Next, format how many decimals to be displayed
        $query = "SELECT $column FROM $tableName";
        for ($j = 0; $j <= $scale; $j++) {
            $options = array('FormatDecimals' => true,'DecimalPlaces' => $j);
            $stmt = sqlsrv_query($conn, $query, array(), $options);

            if (sqlsrv_fetch($stmt)) {
                $value = sqlsrv_get_field($stmt, 0);
                trace("$value\n");

                $expected = numberFormat($input, $j);
                if ($value !== $expected) {
                    echo "testVariousScales ($j): Expected $expected but got $value\n";
                }
            } else {
                fatalError("Round $i scale $j: sqlsrv_fetch failed\n");
            }
        }
    }
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 1);

// Default is no formatting, but set it to false anyway
$conn = AE\connect(array('FormatDecimals' => false));
if (!$conn) {
    fatalError("Could not connect.\n");
}

$tableName = createTestTable($conn);
insertTestData($conn, $tableName);
testVariousScales($conn, $tableName);

dropTable($conn, $tableName); 

sqlsrv_close($conn);

echo "Done\n";
?>
--EXPECT--
Done
