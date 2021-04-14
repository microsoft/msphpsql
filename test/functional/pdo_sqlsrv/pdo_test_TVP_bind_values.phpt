--TEST--
Test Table-valued parameter using bindValue() and random null inputs
--DESCRIPTION--
Test Table-valued parameter using bindValue() instead of bindParam() with random null values.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsSetup.inc');
require_once('MsCommon_mid-refactor.inc');

try {
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName;", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    dropProc($conn, 'SelectTVP');

    $tvpType = 'TestTVP';
    $dropTableType = dropTableTypeSQL($conn, $tvpType);
    $conn->exec($dropTableType);

    // Create table type and a stored procedure
    $conn->exec($createTestTVP);
    $conn->exec($createSelectTVP);
    
    // Create column arrays
    $str = '';
    for ($i = 0; $i < 255; $i++) {
        $str .= chr(($i % 95) + 32);
    }
    $longStr = str_repeat($str, 2000);
    
    $c01 = ['abcde', '', $str];
    $c02 = ['Hello world!', 'ABCDEFGHIJKLMNOP', $longStr];
    $c03 = [1, 0, 1];
    $c04 = [null, 
            null,
            date_create('1955-12-13 12:20:00')];
    $c05 = [date_create('2384-12-31 12:40:12.34565'), null, date_create('1074-12-31 23:59:59.01234')];
    $c06 = ['4CDBC69F-F0EE-4963-8F17-24DD47090126',
            '0F12A09D-D614-4998-AB1F-BD7CDBF6E3FE',
            null];
    $c07 = ['1234567', '-9223372036854775808', '9223372036854775807'];
    $c08 = [null, -1.79E+308, 1.79E+308];
    $c09 = ['31234567890123.141243449787580175325274',
                         '0.000000000000000000000001',
            '99999999999999.999999999999999999999999'];

    // Create a TVP input array
    $nrows = 3;
    $ncols = 9;
    $params = array();
    for ($i = 0; $i < $nrows; $i++) {
        $rowValues = array($c01[$i], $c02[$i], $c03[$i], $c04[$i], $c05[$i], $c06[$i], $c07[$i], $c08[$i], $c09[$i]);
        array_push($params, $rowValues);
    }

    $tvpInput = array($tvpType => $params);

    // Prepare to call the stored procedure
    $stmt = $conn->prepare($callSelectTVP);

    // Bind parameters for the stored procedure
    $stmt->bindValue(1, $tvpInput, PDO::PARAM_LOB);
    $stmt->execute();

    // Verify the results
    $row = 0;
    while ($result = $stmt->fetch(PDO::FETCH_NUM)) {
        // For strings, compare their values
        for ($col = 0; $col < 2; $col++) {
            if ($result[$col] != $params[$row][$col]) {
                echo 'Unexpected data at row ' . $row + 1 . ' and col ' . $col + 1 . PHP_EOL;
                echo 'Expected: ' . $params[$row][$col] . PHP_EOL;
                echo 'Fetched: ' . $result[$col] . PHP_EOL;
            }
        }
        // For other types, print them
        echo 'Row ' . $row + 1 . ': from Col ' . $col + 1 . ' to ' . $ncols . PHP_EOL;
        for ($col = 2; $col < $ncols; $col++) {
            var_dump($result[$col]);
        }
        echo PHP_EOL;
        $row++;
    }
    unset($stmt);

    dropProc($conn, 'SelectTVP');
    $conn->exec($dropTableType);
    
    unset($conn);
    echo "Done" . PHP_EOL;
    
} catch (PDOException $e) {
    var_dump($e->getMessage());
}
?>
--EXPECT--
Row 1: from Col 3 to 9
string(1) "1"
NULL
string(25) "2384-12-31 12:40:12.34565"
string(36) "4CDBC69F-F0EE-4963-8F17-24DD47090126"
string(7) "1234567"
NULL
string(39) "31234567890123.141243449787580175325274"

Row 2: from Col 3 to 9
string(1) "0"
NULL
NULL
string(36) "0F12A09D-D614-4998-AB1F-BD7CDBF6E3FE"
string(20) "-9223372036854775808"
string(10) "-1.79E+308"
string(25) ".000000000000000000000001"

Row 3: from Col 3 to 9
string(1) "1"
string(19) "1955-12-13 12:20:00"
string(25) "1074-12-31 23:59:59.01234"
NULL
string(19) "9223372036854775807"
string(9) "1.79E+308"
string(39) "99999999999999.999999999999999999999999"

Done