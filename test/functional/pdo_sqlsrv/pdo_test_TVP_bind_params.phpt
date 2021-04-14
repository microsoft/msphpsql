--TEST--
Table-valued parameter using bindParam
--DESCRIPTION--
Table-valued parameter using bindParam. This test verifies the fetched results of the basic data types. 
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsSetup.inc');
require_once('MsCommon_mid-refactor.inc');

try {
    date_default_timezone_set('America/Los_Angeles');

    $conn = new PDO("sqlsrv:server = $server; database=$databaseName;", $uid, $pwd);
    $conn->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    $tvpType = 'TVPParam';
    
    dropProc($conn, 'TVPOrderEntry');
    dropTable($conn, 'TVPOrd');
    dropTable($conn, 'TVPItem');
    
    $dropTableType = dropTableTypeSQL($conn, $tvpType);
    $conn->exec($dropTableType);

    // Create tables and a stored procedure
    $conn->exec($createTVPOrd);
    $conn->exec($createTVPItem);
    $conn->exec($createTVPParam);
    $conn->exec($createTVPOrderEntry);
    
    $custCode = 'PDO_123';
    $ordNo = 0;
    $ordDate = null;

    $image1 = fopen($tvpIncPath. $gif1, 'rb');
    $image2 = fopen($tvpIncPath. $gif2, 'rb');
    $image3 = fopen($tvpIncPath. $gif3, 'rb');
    $images = [$image1, $image2, $image3];
    
    for ($i = 0; $i < count($items); $i++) {
        array_push($items[$i], $images[$i]);
    }

    // Create a TVP input array
    $tvpInput = array($tvpType => $items);

    // Prepare to call the stored procedure
    $stmt = $conn->prepare($callTVPOrderEntry);
   
    // Bind parameters for the stored procedure
    $stmt->bindParam(1, $custCode);
    $stmt->bindParam(2, $tvpInput, PDO::PARAM_LOB);
    $stmt->bindParam(3, $ordNo, PDO::PARAM_INT, 10);
    $stmt->bindParam(4, $ordDate, PDO::PARAM_STR, 20);
    $stmt->execute();
    $stmt->closeCursor();

    // Verify the results
    echo "Order Number: $ordNo" . PHP_EOL;
    
    $today = getTodayDateAsString($conn);
    if ($ordDate != $today) {
        echo "Order Date unexpected: ";
        var_dump($ordDate);
    }

    // Fetch a random inserted image from the table and verify them
    $n = rand(10,100);
    $index = $n % count($images);
    
    $tsql = 'SELECT Photo FROM TVPItem WHERE ItemNo = ' . $index + 1;
    $stmt = $conn->query($tsql);
    $stmt->bindColumn('Photo', $photo, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    if ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
        if (!verifyBinaryData($images[$index], $photo)) {
            echo 'Image data corrupted for row '. $index + 1 . PHP_EOL;
        }
    } else {
        echo 'Failed in calling bindColumn' . PHP_EOL;
    }
    unset($photo);

    fclose($image1);
    fclose($image2);
    fclose($image3);

    // Fetch CustID
    $tsql = 'SELECT CustID FROM TVPOrd';
    $stmt = $conn->query($tsql);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $id = $row[0];
    if ($id != $custCode) {
        echo "Customer ID unexpected: " . PHP_EOL;
        var_dump($id);
    }

    // Fetch other basic types
    $stmt = $conn->query($selectTVPItemQuery);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        print_r($row);
    }
    unset($stmt);

    dropProc($conn, 'TVPOrderEntry');
    dropTable($conn, 'TVPOrd');
    dropTable($conn, 'TVPItem');
    $conn->exec($dropTableType);
    
    unset($conn);
    echo "Done" . PHP_EOL;
    
} catch (PDOException $e) {
    var_dump($e->getMessage());
}
?>
--EXPECT--
Order Number: 1
Array
(
    [OrdNo] => 1
    [ItemNo] => 1
    [ProductCode] => 0062836700
    [OrderQty] => 367
    [SalesDate] => 2009-03-12
    [Label] => AWC Tee Male Shirt
    [Price] => 20.75
)
Array
(
    [OrdNo] => 1
    [ItemNo] => 2
    [ProductCode] => 1250153272
    [OrderQty] => 256
    [SalesDate] => 2017-11-07
    [Label] => Superlight Black Bicycle
    [Price] => 998.45
)
Array
(
    [OrdNo] => 1
    [ItemNo] => 3
    [ProductCode] => 1328781505
    [OrderQty] => 260
    [SalesDate] => 2010-03-03
    [Label] => Silver Chain for Bikes
    [Price] => 88.98
)
Done