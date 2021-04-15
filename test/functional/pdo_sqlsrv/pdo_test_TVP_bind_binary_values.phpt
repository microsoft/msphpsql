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

    dropProc($conn, 'SelectTVP2');

    $tvpType = 'TestTVP2';
    $dropTableType = dropTableTypeSQL($conn, $tvpType);
    $conn->exec($dropTableType);

    // Create table type and a stored procedure
    $conn->exec($createTestTVP2);
    $conn->exec($createSelectTVP2);
    
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
    $c09 = [4.321, 'CF43B0B3-E645-48C4-9F25-1A2BB4CE581A', 9999];

    // Create a TVP input array
    $nrows = 3;
    $ncols = 8;
    $params = array();
    for ($i = 0; $i < $nrows; $i++) {
        $rowValues = array($c01[$i], $c02[$i], $c03[$i], $c04[$i], $c05[$i], $c06[$i], $c07[$i], $c08[$i], $c09[$i]);
        array_push($params, $rowValues);
    }

    $tvpInput = array($tvpType => $params);

    // Prepare to call the stored procedure
    $stmt = $conn->prepare($callSelectTVP2);

    // Bind parameters for the stored procedure
    $stmt->bindValue(1, $tvpInput, PDO::PARAM_LOB);
    $stmt->execute();

    // Verify the results
    $row = 0;
    while ($result = $stmt->fetch(PDO::FETCH_NUM)) {
        // Compare the values against the inputs
        for ($col = 0; $col < $ncols; $col++) {
            if ($result[$col] != $params[$row][$col]) {
                echo 'Unexpected data at row ' . ($row + 1) . ' and col ' . ($col + 1) . PHP_EOL;
                echo 'Expected: ' . $params[$row][$col] . PHP_EOL;
                echo 'Fetched: ' . $result[$col] . PHP_EOL;
            }
        }
        $row++;
    }
    unset($stmt);

    dropProc($conn, 'SelectTVP2');
    $conn->exec($dropTableType);
    
    unset($conn);
    echo "Done" . PHP_EOL;
    
} catch (PDOException $e) {
    var_dump($e->getMessage());
}
?>
--EXPECT--
Done