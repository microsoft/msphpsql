--TEST--
Test fetching varbinary max fields with client buffer
--DESCRIPTION--
Test fetching varbinary max fields as streams or strings using client buffer
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--ENV--
PHPT_EXEC=true
--FILE--
<?php
require_once('MsCommon.inc');

function fetchNull($stmt, $sqltype, $message)
{
    $result = sqlsrv_get_field($stmt, 1, $sqltype);
    if (!is_null($result)) {
        echo("$message: expected NULL\n");
    }
}

function fetchNullStream($stmt, $sqltype, $message)
{
    $stream = sqlsrv_get_field($stmt, 1, $sqltype);
    if ($stream !== false) {
        $value = fread($stream, 8192);
        fclose($stream);
        
        if (!empty($value)) {
            echo("$message: expected an empty value\n");
        }
    }
}

function fetchStream($stmt, $test)
{
    global $binaryValue, $hexValue;

    if (!sqlsrv_execute($stmt)) {
        fatalError("fetchStream: failed to execute select");
    }
    if (!sqlsrv_fetch($stmt)) { 
        fatalError("fetchStream: failed to fetch row");
    }

    switch ($test) {
        case 1:
            $sqltype = SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR);
            $type = 'char string';
            $expected = $hexValue;
            break;
        case 2:
            $sqltype = SQLSRV_PHPTYPE_STREAM('UTF-8');
            $type = 'UTF-8 string';
            $expected = $hexValue;
            break;
        case 3:
            $sqltype = SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY);
            $type = 'binary string';
            $expected = $binaryValue;
            break;
        default:
            echo "fetchStream: something went wrong\n";
            break;
    }

    trace("fetchStream ($type):\n");
    $stream = sqlsrv_get_field($stmt, 0, $sqltype);
    if ($stream !== false) {
        $value = '';
        while (!feof($stream)) {
            $value .= fread($stream, 8192);
        }
        fclose($stream);
        if (!checkData($value, $expected)) {
            echo("fetchStream ($type)\nExpected:\n$expected\nActual:\n$value\n");
        }
    } else {
        fatalError("fetchStream ($type) failed");
    }
    
    fetchNullStream($stmt, $sqltype, "fetchStream ($type)\n");
}

function fetchData($stmt, $test)
{
    global $binaryValue, $hexValue;
    
    if (!sqlsrv_execute($stmt)) {
        fatalError("fetchData: failed to execute select");
    }
    if (!sqlsrv_fetch($stmt)) { 
        fatalError("fetchData: failed to fetch row");
    }

    switch ($test) {
        case 1:
            $sqltype = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR);
            $type = 'char string';
            $expected = $hexValue;
            break;
        case 2:
            $sqltype = SQLSRV_PHPTYPE_STRING('UTF-8');
            $type = 'UTF-8 string';
            $expected = $hexValue;
            break;
        case 3:
            $sqltype = SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY);
            $type = 'binary string';
            $expected = $binaryValue;
            break;
        default:
            echo "fetchData: something went wrong\n";
            break;
    }
    
    trace("fetchData ($type):\n");
    $value = sqlsrv_get_field($stmt, 0, $sqltype);
    if (!checkData($value, $expected)) {
        echo("fetchData ($type)\nExpected:\n$expected\nActual:\n$value\n");
    }

    fetchNull($stmt, $sqltype, "fetchData ($type)\n");
}

function runTest($conn, $buffered)
{
    global $tableName, $binaryValue, $hexValue;
    
    $query = "SELECT * FROM $tableName";
    if ($buffered) {
        trace("Test using a client buffer\n");
        $stmt = sqlsrv_prepare($conn, $query, null, array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
    } else {
        trace("Test without using a client buffer\n");
        $stmt = sqlsrv_prepare($conn, $query);
    }
    
    if (!$stmt) {
        fatalError("runTest: failed to prepare select statement");
    }

    fetchData($stmt, 1);
    fetchData($stmt, 2);
    fetchData($stmt, 3);
    
    fetchStream($stmt, 1);
    fetchStream($stmt, 2);
    fetchStream($stmt, 3);
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


$conn = AE\connect();

$tableName = "binary_max_fields";

$columns = array(new AE\ColumnMeta("varbinary(max)", "varbinary_max_col"),
                 new AE\ColumnMeta("varbinary(max)", "varbinary_null_col"));

AE\createTable($conn, $tableName, $columns);

$bin = 'abcdefghijk';
$binaryValue = str_repeat($bin, 400);
$hexValue = strtoupper(bin2hex($binaryValue));

$insertSql = "INSERT INTO $tableName (varbinary_max_col, varbinary_null_col) VALUES (?, ?)";

$params = array(array($binaryValue, null, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')),
                array(null, null, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARBINARY('max')));

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

echo "Done\n";
dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECT--
Done