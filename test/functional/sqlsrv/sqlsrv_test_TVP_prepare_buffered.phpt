--TEST--
Test Table-valued parameter using prepare/execute and some NULL inputs
--DESCRIPTION--
Test Table-valued parameter using prepare/execute and some NULL inputs. This test fetches results as objects using client buffers.
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
$custCode = 'SRV_789';

// 2 - Items TVP
$image3 = fopen($tvpIncPath. $gif3, 'rb');
$images = [null, null, $image3];

for ($i = 0; $i < count($items); $i++) {
    array_push($items[$i], $images[$i]);
}

// Randomly set some values to null
$items[0][1] = null;
$items[2][3] = null;
$items[0][2] = null;

// Create a TVP input array
$tvpInput = array($tvpType => $items);

$ordNo = 0;
$ordDate = null;

$params = array($custCode,
                array($tvpInput, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_TABLE),
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

sqlsrv_next_result($stmt);

// Verify the results
echo "Order Number: $ordNo" . PHP_EOL;

$today = getTodayDateAsString($conn);
if ($ordDate != $today) {
    echo "Order Date unexpected: ";
    var_dump($ordDate);
}

// Fetch the inserted data from the tables
$tsql = 'SELECT CustID FROM TVPOrd';
$stmt = sqlsrv_query($conn, $tsql);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

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

// Fetch the only image from the table that is not NULL
$tsql = 'SELECT ItemNo, Photo FROM TVPItem WHERE Photo IS NOT NULL ORDER BY ItemNo';
$stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"buffered"));
if (!$stmt) {
    print_r(sqlsrv_errors());
}

// Only the last image is not NULL
$index = 2;
while (sqlsrv_fetch($stmt)) {
    $itemNo = sqlsrv_get_field($stmt, 0);
    echo $itemNo . PHP_EOL;
    $photo = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    if (!verifyBinaryStream($images[$index], $photo)) {
        echo "Stream data for image $index corrupted!" . PHP_EOL;
    }
}
sqlsrv_free_stmt($stmt);
fclose($image3);

// Fetch the other columns next
$stmt = sqlsrv_query($conn, $selectTVPItemQuery, array(), array("Scrollable"=>"buffered"));
if (!$stmt) {
    print_r(sqlsrv_errors());
}

while ($item = sqlsrv_fetch_object($stmt)) {
    print_r($item);
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
3
stdClass Object
(
    [OrdNo] => 1
    [ItemNo] => 1
    [ProductCode] => 0062836700
    [OrderQty] => 
    [PackedOn] => 
    [Label] => AWC Tee Male Shirt
    [Price] => 20.75
)
stdClass Object
(
    [OrdNo] => 1
    [ItemNo] => 2
    [ProductCode] => 1250153272
    [OrderQty] => 256
    [PackedOn] => 2017-11-07
    [Label] => Superlight Black Bicycle
    [Price] => 998.45
)
stdClass Object
(
    [OrdNo] => 1
    [ItemNo] => 3
    [ProductCode] => 1328781505
    [OrderQty] => 260
    [PackedOn] => 2010-03-03
    [Label] => 
    [Price] => 88.98
)
Done
