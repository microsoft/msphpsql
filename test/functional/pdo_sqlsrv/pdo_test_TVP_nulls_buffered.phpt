--TEST--
Table-valued parameter with bindParam and named parameters. The initial values of a column are NULLs
--DESCRIPTION--
Test Table-valued parameter with bindParam. The initial values of a column are NULLs. This test verifies the fetched results using client buffers.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsSetup.inc');
require_once('MsCommon_mid-refactor.inc');

function verifyPhoto($image, $photo)
{
    // With PHP 8.1+, bindColumn() of binary fields will be resource
    if (PHP_VERSION_ID >= 80100) {
        return verifyBinaryStream($image, $photo);
    } else {
        return verifyBinaryData($image, $photo);
    }
}

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
    
    // Bind parameters for call to TVPOrderEntry
    $custCode = 'PDO_789';
    $ordNo = 0;
    $ordDate = null;

    // TVP supports column-wise binding
    $image3 = fopen($tvpIncPath. $gif3, 'rb');
    $images = [null, null, $image3];
    
    // Added images to $items
    for ($i = 0; $i < count($items); $i++) {
        array_push($items[$i], $images[$i]);
    }

    // Create a TVP input array
    $tvpInput = array($tvpType => $items);

    // Prepare to call the stored procedure
    $stmt = $conn->prepare($callTVPOrderEntryNamed);
   
    $stmt->bindParam(':id', $custCode);
    $stmt->bindParam(':tvp', $tvpInput, PDO::PARAM_LOB);
    $stmt->bindParam(':ordNo', $ordNo, PDO::PARAM_INT, 10);
    $stmt->bindParam(':ordDate', $ordDate, PDO::PARAM_STR, 20);

    $stmt->execute();
    $stmt->closeCursor();

    // Verify the results
    echo "Order Number: $ordNo" . PHP_EOL;
    
    $today = getTodayDateAsString($conn);
    if ($ordDate != $today) {
        echo "Order Date unexpected: ";
        var_dump($ordDate);
    }

    // Fetch CustID
    $tsql = 'SELECT CustID FROM TVPOrd';
    $stmt = $conn->query($tsql);
    $row = $stmt->fetch(PDO::FETCH_NUM);
    $id = $row[0];
    if ($id != $custCode) {
        echo "Customer ID unexpected: " . PHP_EOL;
        var_dump($id);
    }

    // Fetch the only image from the table that is not NULL
    $tsql = 'SELECT ItemNo, Photo FROM TVPItem WHERE Photo IS NOT NULL ORDER BY ItemNo';
    $stmt = $conn->query($tsql);
    $index = 2;
    $stmt->bindColumn('Photo', $photo, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
    
    if ($row = $stmt->fetch(PDO::FETCH_BOUND)) {
        if (!verifyPhoto($images[$index], $photo)) {
            echo 'Image data corrupted for row '. ($index + 1) . PHP_EOL;
        }
    } else {
        echo 'Failed in calling bindColumn' . PHP_EOL;
    }
    unset($photo);
    fclose($image3);

    // Fetch other basic types
    $stmt = $conn->prepare($selectTVPItemQuery, array(PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED));
    $stmt->execute();
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
    [PackedOn] => 2009-03-12
    [Label] => AWC Tee Male Shirt
    [Price] => 20.75
)
Array
(
    [OrdNo] => 1
    [ItemNo] => 2
    [ProductCode] => 1250153272
    [OrderQty] => 256
    [PackedOn] => 2017-11-07
    [Label] => Superlight Black Bicycle
    [Price] => 998.45
)
Array
(
    [OrdNo] => 1
    [ItemNo] => 3
    [ProductCode] => 1328781505
    [OrderQty] => 260
    [PackedOn] => 2010-03-03
    [Label] => Silver Chain for Bikes
    [Price] => 88.98
)
Done
