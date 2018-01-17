--TEST--
Streaming Field Test
--DESCRIPTION--
Verifies the streaming behavior and proper error handling with Always Encrypted
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

$tableName = "test_max_fields";
AE\createTable($conn, $tableName, array(new AE\ColumnMeta("varchar(max)", "varchar_max_col")));

$inValue = str_repeat("ÃÜðßZZýA©", 600);
$insertSql = "INSERT INTO $tableName (varchar_max_col) VALUES (?)";
$params = array($inValue);

$stmt = sqlsrv_prepare($conn, $insertSql, $params);
if ($stmt) {
    sqlsrv_execute($stmt);
} 

$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_prepare($conn, $query);
if ($stmt) {
    sqlsrv_execute($stmt);
} 

if (!sqlsrv_fetch($stmt)) { 
    fatalError("Failed to fetch row ");
}

$stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));

$success = false;
if ($stream !== false) {
    $value = '';
    $num = 0;
    while (!feof($stream)) {
        $value .= fread($stream, 8192);
    }
    fclose($stream);
    if (checkData($value, $inValue)) {  // compare the data to see if they match!
        $success = true;
    }
}
if ($success) {
    echo "Done.\n";
} else {
    fatalError("Failed to fetch stream ");
}

function checkData($actual, $expected)
{
    $success = true;

    $pos = strpos($actual, $expected);
    if (($pos === false) || ($pos > 1)) {
         $success = false;
    }
      
    if (!$success) {
        trace("\nData error\nExpected:\n$expected\nActual:\n$actual\n");
    }

    return ($success);
}
?>
--EXPECT--
Done.