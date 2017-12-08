--TEST--
Populate different test tables with character fields using empty stream data as inputs
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function sendQueryStream($conn, $query, $value, $fileName)
{
    $fname = fopen($fileName, "r");
    $res = true;
    if (AE\isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, $query, array($value, &$fname), array('SendStreamParamsAtExec' => 0));
        if ($stmt) {
            $res = sqlsrv_execute($stmt);
        }
    } else {
        $stmt = sqlsrv_query($conn, $query, array($value, &$fname), array('SendStreamParamsAtExec' => 0));
    }

    if ($stmt === false || !$res) {
        fclose($fname);
        fatalError("Failed in sendQueryStream for $value\n");
    } 
        
    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);
    fclose($fname);
}

function char2Stream($conn, $fileName)
{
    $tableName = 'streams_empty_char'; 
    // create a test table
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('char(512)', 'c2_char'),
                     new AE\ColumnMeta('varchar(512)', 'c3_varchar'),
                     new AE\ColumnMeta('varchar(max)', 'c4_varchar_max'),
                     new AE\ColumnMeta('text', 'c5_text'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    sqlsrv_free_stmt($stmt);

    // insert data
    sendQueryStream($conn, "INSERT INTO $tableName (c1_int, c2_char) VALUES (?, ?)", 1, $fileName);
    fetchData($conn, $tableName, 1);

    sendQueryStream($conn, "INSERT INTO $tableName (c1_int, c3_varchar) VALUES (?, ?)", 2, $fileName);
    fetchData($conn, $tableName, 2);

    sendQueryStream($conn, "INSERT INTO $tableName (c1_int, c4_varchar_max) VALUES (?, ?)", 3, $fileName);
    fetchData($conn, $tableName, 3);

    sendQueryStream($conn, "INSERT INTO $tableName (c1_int, c5_text) VALUES (?, ?)", 4, $fileName);
    fetchData($conn, $tableName, 4);
    
    dropTable($conn, $tableName);
}

function fetchData($conn, $tableName, $fld)
{
    if (AE\isColEncrypted()) {
        // bind param when AE is enabled
        $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName WHERE c1_int = ?", array($fld));
    } else {
        $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName WHERE c1_int = $fld");
    }
    sqlsrv_execute($stmt);
    $result = sqlsrv_fetch($stmt);
    $stream = sqlsrv_get_field($stmt, $fld, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
    var_dump($stream);

    sqlsrv_execute($stmt);
    $result = sqlsrv_fetch($stmt);
    $value = sqlsrv_get_field($stmt, $fld, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY));
    var_dump($value);

    sqlsrv_free_stmt($stmt);
}

echo "\nTest begins...\n";
try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();

    // create an empty file
    $fileName = "sqlsrv_streams_empty_char.dat";
    $fp = fopen($fileName, "wb");
    fclose($fp);

    char2Stream($conn, $fileName);

    // delete the file
    unlink($fileName);
    sqlsrv_close($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿﻿
Test begins...
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)
bool(false)

Done
