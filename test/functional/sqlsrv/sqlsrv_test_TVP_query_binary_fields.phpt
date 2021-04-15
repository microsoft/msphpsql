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
$conn = connect(array('CharacterSet'=>'UTF-8'));

dropProc($conn, 'SelectTVP2');
$tvpType = 'TestTVP2';
$dropTableType = dropTableTypeSQL($conn, $tvpType);
sqlsrv_query($conn, $dropTableType);

// Create table type and a stored procedure
sqlsrv_query($conn, $createTestTVP2);
sqlsrv_query($conn, $createSelectTVP2);

// Create column arrays
$str1 = "Šỡოē šâოрĺẻ ÅŚÇÏЇ-ťếхţ";
$longStr1 = str_repeat($str1, 1500);
$str2 = pack("H*", '49006427500048005000' );  // I'LOVE_SYMBOL'PHP
$longStr2 = str_repeat($str2, 2000);

$bin1 = pack('H*', '0FD1CEFACE');
$bin2 = pack('H*', '0001020304');
$bin3 = hex2bin('616263646566676869');  // abcdefghi
$bin4 = pack('H*', '7A61CC86C7BDCEB2F18FB3BF');

$xml = "<XmlTestData><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1></XmlTestData>";

$c01 = [null, $str1, $str2];
$c02 = [null, $longStr1, $longStr2];
$c03 = [null, null, 999];
$c04 = [null, 3.1415927, null];
$c05 = [$bin1, null, $bin2];
$c06 = [null, $bin3, $bin4];
$c07 = [null, '1234.56', '9876.54'];
$c08 = [null, null, $xml];

// Create a TVP input array
$nrows = 3;
$ncols = 8;
$inputs = array();
for ($i = 0; $i < $nrows; $i++) {
    $rowValues = array($c01[$i], $c02[$i], $c03[$i], $c04[$i], $c05[$i], $c06[$i], $c07[$i], $c08[$i]);
    array_push($inputs, $rowValues);
}

$tvpInput = array($tvpType => $inputs);
$params = array(array($tvpInput, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_TABLE, SQLSRV_SQLTYPE_TABLE));

$stmt = sqlsrv_query($conn, $callSelectTVP2, $params);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

// Verify the results
$row = 0;
while ($result = sqlsrv_fetch($stmt, SQLSRV_FETCH_NUMERIC)) {
    // For strings, compare their values
    for ($col = 0; $col < 2; $col++) {
        $field = sqlsrv_get_field($stmt, $col, SQLSRV_PHPTYPE_STRING('UTF-8'));
        if ($field != $inputs[$row][$col]) {
            echo 'Unexpected data at row ' . ($row + 1) . ' and col ' . ($col + 1) . PHP_EOL;
            echo 'Expected: ' . $inputs[$row][$col] . PHP_EOL;
            echo 'Fetched: ' . $field . PHP_EOL;
        }
    }
    // For other types, print them
    echo 'Row ' . ($row + 1) . ': from Col ' . ($col + 1) . ' to ' . $ncols . PHP_EOL;
    for ($col = 2; $col < $ncols; $col++) {
        $field = sqlsrv_get_field($stmt, $col, SQLSRV_PHPTYPE_STRING('UTF-8'));
        var_dump($field);
    }
    echo PHP_EOL;
    $row++;
}
sqlsrv_free_stmt($stmt);

dropProc($conn, 'SelectTVP2');
sqlsrv_query($conn, $dropTableType);
sqlsrv_close($conn);

echo "Done" . PHP_EOL;
?>
--EXPECT--
Row 1: from Col 3 to 8
NULL
NULL
string(10) "0FD1CEFACE"
NULL
NULL
NULL

Row 2: from Col 3 to 8
NULL
string(9) "3.1415927"
NULL
string(18) "616263646566676869"
string(9) "1234.5600"
NULL

Row 3: from Col 3 to 8
string(3) "999"
NULL
string(10) "0001020304"
string(24) "7A61CC86C7BDCEB2F18FB3BF"
string(9) "9876.5400"
string(120) "<XmlTestData><Letters1>The quick brown fox jumps over the lazy dog</Letters1><Digits1>0123456789</Digits1></XmlTestData>"

Done