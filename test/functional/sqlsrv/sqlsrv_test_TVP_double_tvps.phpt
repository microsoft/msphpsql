--TEST--
Test Table-valued parameter with a stored procedure that takes two TVPs
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

date_default_timezone_set('America/Los_Angeles');

function cleanup($conn, $schema, $pre2016)
{
    if ($pre2016) {
        sqlsrv_query($conn, "DROP PROCEDURE [$schema].[AddReview]");
        sqlsrv_query($conn, "DROP TYPE [$schema].[TestTVP3]");
        sqlsrv_query($conn, "DROP TYPE [$schema].[SupplierType]");
        sqlsrv_query($conn, "DROP SCHEMA [$schema]");
    } else {
        global $dropSchema;
        
        $dropProcedure = dropProcSQL($conn, "[$schema].[AddReview]");
        sqlsrv_query($conn, $dropProcedure);

        $dropTableType = dropTableTypeSQL($conn, "TestTVP3", $schema);
        sqlsrv_query($conn, $dropTableType);
        $dropTableType = dropTableTypeSQL($conn, "SupplierType", $schema);
        sqlsrv_query($conn, $dropTableType);
        
        sqlsrv_query($conn, $dropSchema);
    }
}

sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

$conn = connect(array('CharacterSet'=>'UTF-8', 'ReturnDatesAsStrings' => true));

$stmt = sqlsrv_query($conn, "SELECT @@VERSION");
if (sqlsrv_fetch($stmt)) {
    $result = sqlsrv_get_field($stmt, 0);
}
$version = explode(' ', $result);
$pre2016 = ($version[3] < '2016');

// Use a different schema instead of dbo
$schema = 'Sales DB';
cleanup($conn, $schema, $pre2016);

// Create table types and stored procedures
sqlsrv_query($conn, $createSchema);
sqlsrv_query($conn, $createTestTVP3);
sqlsrv_query($conn, $createSupplierType);
sqlsrv_query($conn, $createAddReview);

// Create the TVP input arrays
$inputs1 = [
    [12345, 'Large大'],
    [67890, 'Medium中'],
    [45678, 'Small小'],
];

$inputs2 = [
    ['ABCDE', 12345, '2019-12-31 23:59:59.123456'],
    ['FGHIJ', 67890, '2000-07-15 12:30:30.5678'],
    ['KLMOP', 45678, '2007-04-08 06:15:15.333'],
];

$tvpType1 = "SupplierType";
$tvpType2 = "TestTVP3";

$tvpInput1 = array($tvpType1 => $inputs1, $schema);
$tvpInput2 = array($tvpType2 => $inputs2, $schema);

$image = fopen($tvpIncPath. 'awc_tee_male_large.gif', 'rb');

$params = array(array($tvpInput1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_TABLE, SQLSRV_SQLTYPE_TABLE),
                array($tvpInput2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_TABLE, SQLSRV_SQLTYPE_TABLE),
                array($image, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('MAX')));

$stmt = sqlsrv_query($conn, $callAddReview, $params);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

// Verify the results
$row = 0;
while ($result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    print_r($result);
}
sqlsrv_next_result($stmt);
while ($result = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC)) {
    print_r($result);
}
sqlsrv_next_result($stmt);
if (sqlsrv_fetch($stmt)) {
    $photo = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    if (!verifyBinaryStream($image, $photo)) {
        echo 'Image data is corrupted' . PHP_EOL;
    }
} else {
    echo 'Something went wrong reading the image' . PHP_EOL;
}

fclose($image);
cleanup($conn, $schema, $pre2016);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo "Done" . PHP_EOL;
?>
--EXPECT--
Array
(
    [SupplierId] => 12345
    [SupplierName] => Large大
)
Array
(
    [SupplierId] => 67890
    [SupplierName] => Medium中
)
Array
(
    [SupplierId] => 45678
    [SupplierName] => Small小
)
Array
(
    [SupplierId] => 12345
    [SalesDate] => 2019-12-31 23:59:59.1234560
    [Review] => ABCDE
)
Array
(
    [SupplierId] => 67890
    [SalesDate] => 2000-07-15 12:30:30.5678000
    [Review] => FGHIJ
)
Array
(
    [SupplierId] => 45678
    [SalesDate] => 2007-04-08 06:15:15.3330000
    [Review] => KLMOP
)
Done