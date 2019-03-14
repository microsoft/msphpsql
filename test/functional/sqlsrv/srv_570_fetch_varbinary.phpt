--TEST--
GitHub issue #570 - fetching a varbinary field as a stream using client buffer
--DESCRIPTION--
Verifies that a varbinary field (with size or max) can be successfully fetched even when a client buffer is used. There is no more "Invalid cursor state" error.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

function compareStreams($input, $result)
{
    while (($line1 = fread($input, 80)) && ($line2 = fread($result, 80))) {
        if ($line1 != $line2) {
            echo "Stream not identical\n";
            break;
        }
    }
}

function fetchData($stmt, $data, $buffered)
{
    if ($buffered) {
        $result = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);
    } else {
        $result = sqlsrv_fetch($stmt);
    }
    if ($result === false) {
        fatalError('Error when fetching data');
    }

    // Get image data as a binary stream
    $image = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    if ($image === false) {
        fatalError('Error in calling sqlsrv_get_field');
    }

    // Does the retrieved stream match with the original?
    compareStreams($data, $image);

    fclose($image);
}

function runQuery($conn, $data, $tsql, $buffered, $prepared)
{
    if ($prepared) {
        if ($buffered) {
            // Pick a random size for the buffer large enough for this test
            $stmt = sqlsrv_prepare($conn, $tsql, array(), array("Scrollable" => SQLSRV_CURSOR_CLIENT_BUFFERED, "ClientBufferMaxKBSize" => 51200));
        } else {
            $stmt = sqlsrv_prepare($conn, $tsql);
        }
        if ($stmt === false) {
            fatalError("Error in preparing the query ($buffered).");
        }
        
        $result = sqlsrv_execute($stmt);
        if ($result === false) {
            fatalError("Error in executing the query ($buffered).");
        }
    } else {
        if ($buffered) {
            // Use the default buffer size in this case
            $stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));
        } else {
            $stmt = sqlsrv_query($conn, $tsql);
        }
    
        if ($stmt === false) {
            fatalError("Error in sqlsrv_query ($buffered).");
        }
    }
    
    fetchData($stmt, $data, $buffered);
}

function runTest($conn, $columnType, $path)
{
    $tableName = 'srvTestTable_570' . rand(0, 10);
    dropTable($conn, $tableName);
    
    // Create the test table with only one column
    $tsql = "CREATE TABLE $tableName([picture] $columnType NOT NULL)";
    $stmt = sqlsrv_query($conn, $tsql);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    // Insert php.gif as stream data
    $tsql = "INSERT INTO $tableName (picture) VALUES (?)";
    
    $data = fopen($path, 'rb');
    if (!$data) {
        fatalError('Could not open image for reading.');
    }

    $params = array($data, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    $stmt = sqlsrv_query($conn, $tsql, array($params));
    if ($stmt === false) {
        fatalError("Failed to insert image into $tableName");
    }
    do {
        $read = sqlsrv_send_stream_data($stmt);
    } while ($read);
    sqlsrv_free_stmt($stmt);

    // Start testing, with or without client buffer, using prepared statement or direct query
    $tsql = "SELECT picture FROM $tableName";
    runQuery($conn, $data, $tsql, false, true);
    runQuery($conn, $data, $tsql, true, true);
    runQuery($conn, $data, $tsql, false, false);
    runQuery($conn, $data, $tsql, true, false);
    
    // Clean up
    fclose($data);

    dropTable($conn, $tableName);
}

require_once('MsCommon.inc');

$conn = connect();
if ($conn === false) {
    die(print_r(sqlsrv_errors(), true));
}

if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
    $pic = '\\php.gif';
} else { // other than Windows
    $pic = '/php.gif';
}
$path = dirname($_SERVER['PHP_SELF']) . $pic;

runTest($conn, 'VARBINARY(MAX)', $path);
runTest($conn, 'VARBINARY(4096)', $path);

echo "Done\n";

sqlsrv_close($conn);
?>
--EXPECT--
Done