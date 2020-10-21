--TEST--
Test fetching varchar max and varbinary fields with client buffer
--DESCRIPTION--
Test fetching varbinary and varchar max fields as streams or strings with client buffer
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

$tableName = "test_max_fields";

$columns = array(new AE\ColumnMeta("varchar(max)", "varchar_max_col"),
                 new AE\ColumnMeta("varbinary(max)", "varbinary_max_col"));

AE\createTable($conn, $tableName, $columns);

$strValue = str_repeat("ÃÜðßZZýA©", 600);

$input = strtoupper(bin2hex('abcdefghijklmnopqrstuvwxyz'));
$binaryValue = str_repeat($input, 100);

$insertSql = "INSERT INTO $tableName (varchar_max_col, varbinary_max_col) VALUES (?, ?)";

$params = array($strValue, array($binaryValue, null, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARBINARY('max')));

$stmt = sqlsrv_prepare($conn, $insertSql, $params);
if ($stmt) {
    $res = sqlsrv_execute($stmt);
    if (!$res) {
        fatalError("Failed to insert data");
    }
} else {
    fatalError("Failed to prepare insert statement");
}

$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_prepare($conn, $query, null, array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
if ($stmt) {
    $res = sqlsrv_execute($stmt);
    if (!$res) {
        fatalError("Failed to fetch data");
    }
} else {
    fatalError("Failed to prepare select statement");
}

if (!sqlsrv_fetch($stmt)) { 
    fatalError("Failed to fetch row");
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
    if (checkData($value, $strValue)) {  // compare the data to see if they match!
        $success = true;
    }
}
if (!$success) {
    fatalError("Failed to fetch stream ");
}

$value = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
if (!checkData($value, $binaryValue)) {  // compare the data to see if they match!
    echo("Expected:\n$binaryValue\nActual:\n$value\n");
}

echo "Done.\n";
dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

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