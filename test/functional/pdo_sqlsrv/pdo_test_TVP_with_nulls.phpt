--TEST--
Test Table-valued parameter using bindParam and some NULL inputs
--DESCRIPTION--
Test Table-valued parameter using bindParam with some NULL input values. This test verifies the fetched results of all columns.
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
    
    // Bind parameters for call to TVPOrderEntry
    $custCode = 'PDO_456';
    $ordNo = 0;
    $ordDate = null;

    // Add null image to $items
    for ($i = 0; $i < count($items); $i++) {
        array_push($items[$i], null);
    }
    
    // Randomly set some values to null
    $items[1][0] = null;
    $items[0][2] = null;
    
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

    // Fetch all columns
    $tsql = 'SELECT * FROM TVPItem ORDER BY ItemNo';
    $stmt = $conn->query($tsql);
    if ($row = $stmt->fetchall(PDO::FETCH_NUM)) {
        var_dump($row);
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
array(3) {
  [0]=>
  array(8) {
    [0]=>
    string(1) "1"
    [1]=>
    string(1) "1"
    [2]=>
    string(10) "0062836700"
    [3]=>
    string(3) "367"
    [4]=>
    NULL
    [5]=>
    string(18) "AWC Tee Male Shirt"
    [6]=>
    string(5) "20.75"
    [7]=>
    NULL
  }
  [1]=>
  array(8) {
    [0]=>
    string(1) "1"
    [1]=>
    string(1) "2"
    [2]=>
    NULL
    [3]=>
    string(3) "256"
    [4]=>
    string(10) "2017-11-07"
    [5]=>
    string(24) "Superlight Black Bicycle"
    [6]=>
    string(6) "998.45"
    [7]=>
    NULL
  }
  [2]=>
  array(8) {
    [0]=>
    string(1) "1"
    [1]=>
    string(1) "3"
    [2]=>
    string(10) "1328781505"
    [3]=>
    string(3) "260"
    [4]=>
    string(10) "2010-03-03"
    [5]=>
    string(22) "Silver Chain for Bikes"
    [6]=>
    string(5) "88.98"
    [7]=>
    NULL
  }
}
Done