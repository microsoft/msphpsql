--TEST--
Test fetching varchar and nvarchar max fields
--DESCRIPTION--
Test fetching varchar and nvarchar max fields as streams or strings with or without client buffer
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--ENV--
PHPT_EXEC=true
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

$tableName = "char_max_fields";
$columns = array(new AE\ColumnMeta("varchar(max)", "varchar_max_col"),
                 new AE\ColumnMeta("nvarchar(max)", "nvarchar_max_col"));

AE\createTable($conn, $tableName, $columns);

$strValue = str_repeat("SimpleTest", 450);
$nstrValue = str_repeat("ÃÜðßZZýA©", 600);

$insertSql = "INSERT INTO $tableName (varchar_max_col, nvarchar_max_col) VALUES (?, ?)";
$params = array(array($strValue, null, null, SQLSRV_SQLTYPE_VARCHAR('max')),
                array($nstrValue, null, SQLSRV_PHPTYPE_STRING('UTF-8'), SQLSRV_SQLTYPE_NVARCHAR('max')));

$stmt = sqlsrv_prepare($conn, $insertSql, $params);
if ($stmt) {
    $res = sqlsrv_execute($stmt);
    if (!$res) {
        fatalError("Failed to insert data");
    }
} else {
    fatalError("Failed to prepare insert statement");
}

runTest($conn, false);
runTest($conn, true);

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo "Done\n";

///////////////////////////////////////////////////////////////////////////////////////////////
function runTest($conn, $buffered)
{
    global $tableName, $strValue, $nstrValue;
    
    trace("runTest ($buffered)\n");
    $query = "SELECT * FROM $tableName";
    if ($buffered) {
        $stmt = sqlsrv_prepare($conn, $query, null, array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
    } else {
        $stmt = sqlsrv_prepare($conn, $query);
    }
    if (!$stmt) {
        fatalError("runTest ($buffered): failed to prepare select statement");
    }

    if (!sqlsrv_execute($stmt)) {
        fatalError("runTest ($buffered): failed to execute select");
    }
    if (!sqlsrv_fetch($stmt)) { 
        fatalError("runTest ($buffered): failed to fetch data");
    }

    fetchAsString($stmt, 0, $strValue);
    fetchAsString($stmt, 1, $nstrValue);

    if (!sqlsrv_execute($stmt)) {
        fatalError("runTest ($buffered): failed to execute select");
    }
    if (!sqlsrv_fetch($stmt)) { 
        fatalError("runTest ($buffered): failed to fetch data");
    }

    fetchAsStream($stmt, 0, $strValue);
    fetchAsStream($stmt, 1, $nstrValue);
}

function fetchAsString($stmt, $index, $expected)
{
    trace("fetchAsString ($index):\n");
    $sqltype = ($index > 0) ? SQLSRV_PHPTYPE_STRING('UTF-8') : SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
    $value = sqlsrv_get_field($stmt, $index, $sqltype);
    if (!checkData($value, $expected)) {
        echo("fetchAsString ($index) expected:\n$expected\nActual:\n$value\n");
    }
}

function fetchAsStream($stmt, $index, $expected)
{
    trace("fetchAsStream ($index):\n");
    $sqltype = ($index > 0) ? SQLSRV_PHPTYPE_STREAM('UTF-8') : SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR);

    $stream = sqlsrv_get_field($stmt, $index, $sqltype);
    if ($stream !== false) {
        $value = '';
        while (!feof($stream)) {
            $value .= fread($stream, 8192);
        }
        fclose($stream);
        if (!checkData($value, $expected)) {
            echo("fetchAsStream ($index) expected:\n$expected\nActual:\n$value\n");
        }
    }
}

function checkData($actual, $expected)
{
    $success = true;

    $pos = strpos($actual, $expected);
    if (($pos === false) || ($pos > 1)) {
        $success = false;
    }
      
    return ($success);
}
?>
--EXPECT--
Done