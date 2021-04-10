--TEST--
Table-valued parameter with string keys using prepare/execute and all inputs provided but in random order
--DESCRIPTION--
Table-valued parameter with string keys using prepare/execute and only one column has NULL values. This test verifies the fetched results of using client buffers.
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
// TVP supports column-wise binding
$image3 = fopen($tvpIncPath. $gif3, 'rb');
$images = [null, null, $image3];

// Create an array of column inputs
$columns = array('photo'=>$images,
                 'productCode'=>array_column($items, 0),
                 'price'=>array_column($items, 4),
                 'OrderQty'=>array_column($items, 1),
                 'Label'=>array_column($items, 3),
                 'SalesDate'=>array_column($items, 2));

// Create a TVP input array
$tvpInput = array($tvpType);
array_push($tvpInput, $columns);

$ordNo = 0;
$ordDate = null;

$params = array($custCode,
                array($tvpInput, null, null, SQLSRV_SQLTYPE_TABLE),
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

while ($row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC)) {
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
3
Array
(
    [0] => 1
    [1] => 1
    [2] => 0062836700
    [3] => 367
    [4] => 2009-03-12
    [5] => AWC Tee Male Shirt
    [6] => 20.75
)
Array
(
    [0] => 1
    [1] => 2
    [2] => 1250153272
    [3] => 256
    [4] => 2017-11-07
    [5] => Superlight Black Bicycle
    [6] => 998.45
)
Array
(
    [0] => 1
    [1] => 3
    [2] => 1328781505
    [3] => 260
    [4] => 2010-03-03
    [5] => Silver Chain for Bikes
    [6] => 88.98
)
Done
