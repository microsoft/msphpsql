--TEST--
reading streams of various types with a base64 decoding filter on top of them.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', false);
require_once('MsCommon.inc');

function runTest($fieldType)
{
    // change the input field type for each run
    prepareParams($params, $fieldType);
    
    $conn = AE\connect();
    
    $originalStream = populateTestTable($conn, $params);

    ($stmt = sqlsrv_query($conn, $params['selectQuery']))
        || die(print_r(sqlsrv_errors(), true));

    sqlsrv_fetch($stmt)
        || die(print_r(sqlsrv_errors(), true));

    $stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM("char"));
    if ($stream) {
        stream_filter_append($originalStream, "convert.base64-encode")
            || die(print_r(error_get_last()));
            
        while (($originalLine = fread($originalStream, 80)) &&
                ($dbLine = fread($stream, 80))) {
            if ($originalLine != $dbLine) {
                die("Not identical");
            }
        }
    } else {
        fatalError('Fetching data stream failed!');
    }
    dropTable($conn, $params['tableName']);
    
    sqlsrv_free_stmt($stmt) || die(print_r(sqlsrv_errors(), true));

    sqlsrv_close($conn) || die(print_r(sqlsrv_errors(), true));
    
}

runTest("varchar(max)");
// runTest("varbinary(max)");
runTest("nvarchar(max)");

echo "Test successful.\n";

function populateTestTable($conn, $params)
{
    $tblName = $params['tableName'];
    $colName = $params['columnName']; 
    $fieldType = $params['fieldType'];
    
    // Create a test table of a single column of a certain field type
    $columns = array(new AE\ColumnMeta($fieldType, $colName));
    $stmt = AE\createTable($conn, $tblName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tblName\n");
    }
    
    ($data = fopen($params['testImageURL'], "rb")) || die("Couldn't open image for reading.");
    
    stream_filter_append($data, "convert.base64-encode")
        || die(print_r(error_get_last(), true));
    
    if (AE\isColEncrypted()) {
        $stmt = AE\insertRow($conn, $tblName, array($colName => $data));
    } else {
        $insertQuery = $params['insertQuery'];
        $stmt = sqlsrv_query($conn, $insertQuery, array($data));
    }
  
    if ($stmt) {
        do {
            $read = sqlsrv_send_stream_data($stmt);
            if ($read === false) {
                die(print_r(sqlsrv_errors(), true));
            }
        } while ($read);
        
        fclose($data) || die(print_r(error_get_last(), true));
        
        sqlsrv_free_stmt($stmt) || die(print_r(sqlsrv_errors(), true));
    } else {
        die(print_r(sqlsrv_errors(), true));
    }

    return fopen($params['testImageURL'], "rb"); 
}

function prepareParams(&$arr, $fieldType)
{
    if (isWindows()) {
        $phpgif = '\\php.gif';
    } else {
        $phpgif = '/php.gif';
    }

    $arr['tableName'] = $tblName = "B64TestTable"; 
    $arr['columnName'] = $colName = "Base64Image";
    $arr['fieldType'] = $fieldType;
    $arr['insertQuery'] = "INSERT INTO $tblName ($colName) VALUES (?)";
    $arr['selectQuery'] = "SELECT TOP 1 $colName FROM $tblName";
    $arr['testImageURL'] = dirname($_SERVER['PHP_SELF']) . $phpgif; // use this when no http access
}

?>
--EXPECT--
Test successful.
