--TEST--
Table-valued parameter with string keys using prepare/execute and NULL input values
--DESCRIPTION--
Table-valued parameter with string keys using prepare/execute, and inputs may contain NULLs and are in random order. This test verifies the fetched results of all columns.
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

    $connectionInfo = "";
    $conn = new PDO("sqlsrv:server = $server; database=$databaseName; $connectionInfo", $uid, $pwd);
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
    $custCode = 'PDO_456';
    $ordNo = 0;
    $ordDate = null;

    // TVP supports column-wise binding
    // Create an array of column inputs with string keys
    $columns = array('label'=>array_column($items, 3),
                     'price'=>array(),
                     'OrderQty'=>array_column($items, 1),
                     'productcode'=>array_column($items, 0));

    // Create a TVP input array
    $tvpInput = array($tvpType);
    array_push($tvpInput, $columns);

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

    // Fetch all columns
    $tsql = 'SELECT * FROM TVPItem ORDER BY ItemNo';
    $stmt = $conn->query($selectTVPItemQuery);
    while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
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
    [0] => 1
    [1] => 1
    [2] => 0062836700
    [3] => 367
    [4] => 
    [5] => AWC Tee Male Shirt
    [6] => 
)
Array
(
    [0] => 1
    [1] => 2
    [2] => 1250153272
    [3] => 256
    [4] => 
    [5] => Superlight Black Bicycle
    [6] => 
)
Array
(
    [0] => 1
    [1] => 3
    [2] => 1328781505
    [3] => 260
    [4] => 
    [5] => Silver Chain for Bikes
    [6] => 
)
Done
