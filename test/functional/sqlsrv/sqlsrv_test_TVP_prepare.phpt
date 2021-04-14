--TEST--
Table-valued parameter with string keys using prepare/execute and some inputs are NULLs
--DESCRIPTION--
Table-valued parameter with string keys using prepare/execute. Some columns may be NULLs, provided in random order. This test verifies the fetched results of the basic data types.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

date_default_timezone_set('America/Los_Angeles');

sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

$conn = AE\connect(array('ReturnDatesAsStrings' => true));

$tvpType = 'TVPParam';

dropProc($conn, 'TVPOrderEntry');
dropTable($conn, 'TVPOrd');
dropTable($conn, 'TVPItem');

$dropTableType = dropTableTypeSQL($conn, $tvpType);
sqlsrv_query($conn, $dropTableType);

// Create tables
sqlsrv_query($conn, $createTVPOrd);
sqlsrv_query($conn, $createTVPItem);

// Create TABLE type for use as a TVP
sqlsrv_query($conn, $createTVPParam);

// Create procedure with TVP parameters
sqlsrv_query($conn, $createTVPOrderEntry);

// Bind parameters for call to TVPOrderEntry
$custCode = 'SRV_000';

// 2 - Items TVP
// TVP supports column-wise binding
$image1 = fopen($tvpIncPath. $gif1, 'rb');
$image2 = fopen($tvpIncPath. $gif2, 'rb');
$image3 = fopen($tvpIncPath. $gif3, 'rb');
$images = [$image1, $image2, $image3];

for ($i = 0; $i < count($items); $i++) {
    array_push($items[$i], $images[$i]);
}

// Create a TVP input array
$tvpInput = array($tvpType => $items);

$ordNo = 0;
$ordDate = null;

$params = array($custCode,
                array($tvpInput, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_TABLE, SQLSRV_SQLTYPE_TABLE),
                array(&$ordNo, SQLSRV_PARAM_OUT),
                array(&$ordDate, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR)));

$stmt = sqlsrv_prepare($conn, $callTVPOrderEntry, $params);
if (!$stmt) {
    print_r(sqlsrv_errors());
}
$res = sqlsrv_execute($stmt);
if (!$res) {
    print_r(sqlsrv_errors());
}

fclose($image1);
fclose($image2);
fclose($image3);

sqlsrv_next_result($stmt);

// Verify the results
echo "Order Number: $ordNo" . PHP_EOL;

$today = getTodayDateAsString($conn);
if ($ordDate != $today) {
    echo "Order Date unexpected: ";
    var_dump($ordDate);
}

// Fetch CustID
$tsql = 'SELECT CustID FROM TVPOrd';
$stmt = sqlsrv_query($conn, $tsql);

if ($result = sqlsrv_fetch( $stmt, SQLSRV_FETCH_NUMERIC)) {
    $id = sqlsrv_get_field($stmt, 0);
    if ($id != $custCode) {
        echo "Customer ID unexpected: " . PHP_EOL;
        var_dump($id);
    }
} else {
    echo "Failed in fetching from TVPOrd: " . PHP_EOL;
    print_r(sqlsrv_errors());
}

$stmt = sqlsrv_query($conn, $selectTVPItemQuery);
while ($row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC)) {
    print_r($row);
}

sqlsrv_free_stmt($stmt);

dropProc($conn, 'TVPOrderEntry');
dropTable($conn, 'TVPOrd');
dropTable($conn, 'TVPItem');
sqlsrv_query($conn, $dropTableType);

sqlsrv_close($conn);
echo "Done" . PHP_EOL;
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