--TEST--
Test Table-valued parameter using direct queries and sqlsrv_send_stream_data with random null inputs
--DESCRIPTION--
Test Table-valued parameter using direct queries and sqlsrv_send_stream_data with random null inputs. This test verifies the fetched results of all columns.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

date_default_timezone_set('America/Los_Angeles');

sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

$conn = connect(array('ReturnDatesAsStrings' => true));

dropProc($conn, 'SelectTVP');

$tvpType = 'TestTVP';
$dropTableType = dropTableTypeSQL($conn, $tvpType);
sqlsrv_query($conn, $dropTableType);

// Create table type and a stored procedure
sqlsrv_query($conn, $createTestTVP);
sqlsrv_query($conn, $createSelectTVP);

// Create column arrays
$str = '';
for ($i = 0; $i < 255; $i++) {
    $str .= chr(($i % 95) + 32);
}
$longStr = str_repeat($str, 3000);

$c01 = [$str, 'ABCDE', ''];
$c02 = ['abcdefghijklmnopqrstuvwxyz', null, $longStr];
$c03 = [null, 0, 1];
$c04 = [null,
        date_create('1997-02-13 12:43:10'),
        null];
$c05 = ["2010-12-31 12:40:12.56679", null, "1965-02-18 23:59:59.43258"];
$c06 = ['4CDBC69F-F0EE-4963-8F17-24DD47090126',
        '0F12A09D-D614-4998-AB1F-BD7CDBF6E3FE',
        null];
$c07 = [null, '-9223372036854775808', '9223372036854775807'];
$c08 = [null, -1.79E+308, 1.79E+308];
$c09 = ['31234567890123.141243449787580175325274',
                     '0.000000000000000000000001',
        '99999999999999.999999999999999999999999'];

// Create a TVP input array
$nrows = 3;
$ncols = 9;
$inputs = array();
for ($i = 0; $i < $nrows; $i++) {
    $rowValues = array($c01[$i], $c02[$i], $c03[$i], $c04[$i], $c05[$i], $c06[$i], $c07[$i], $c08[$i], $c09[$i]);
    array_push($inputs, $rowValues);
}

$tvpInput = array($tvpType => $inputs);
$params = array(array($tvpInput, null, SQLSRV_PHPTYPE_TABLE, SQLSRV_SQLTYPE_TABLE));

$options = array("SendStreamParamsAtExec" => 0);
$stmt = sqlsrv_query($conn, $callSelectTVP, $params, $options);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

// Now call sqlsrv_send_stream_data in a loop
while (sqlsrv_send_stream_data($stmt)) {
}

// Verify the results
$row = 0;
while ($result = sqlsrv_fetch($stmt, SQLSRV_FETCH_NUMERIC)) {
    // For strings, compare their values
    for ($col = 0; $col < 2; $col++) {
        $field = sqlsrv_get_field($stmt, $col, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        if ($field != $inputs[$row][$col]) {
            echo 'Unexpected data at row ' . ($row + 1) . ' and col ' . ($col + 1) . PHP_EOL;
            echo 'Expected: ' . $inputs[$row][$col] . PHP_EOL;
            echo 'Fetched: ' . $field . PHP_EOL;
        }
    }
    // For other types, print them
    echo 'Row ' . ($row + 1) . ': from Col ' . ($col + 1) . ' to ' . $ncols . PHP_EOL;
    for ($col = 2; $col < $ncols; $col++) {
        $field = sqlsrv_get_field($stmt, $col);
        var_dump($field);
    }
    echo PHP_EOL;
    $row++;
}
sqlsrv_free_stmt($stmt);

dropProc($conn, 'SelectTVP');
sqlsrv_query($conn, $dropTableType);
sqlsrv_close($conn);

echo "Done" . PHP_EOL;
?>
--EXPECT--
Row 1: from Col 3 to 9
NULL
NULL
string(25) "2010-12-31 12:40:12.56679"
string(36) "4CDBC69F-F0EE-4963-8F17-24DD47090126"
NULL
NULL
string(39) "31234567890123.141243449787580175325274"

Row 2: from Col 3 to 9
int(0)
string(19) "1997-02-13 12:43:00"
NULL
string(36) "0F12A09D-D614-4998-AB1F-BD7CDBF6E3FE"
string(20) "-9223372036854775808"
float(-1.79E+308)
string(25) ".000000000000000000000001"

Row 3: from Col 3 to 9
int(1)
NULL
string(25) "1965-02-18 23:59:59.43258"
NULL
string(19) "9223372036854775807"
float(1.79E+308)
string(39) "99999999999999.999999999999999999999999"

Done
