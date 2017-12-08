--TEST--
Populate different unicode character fields using null stream data as inputs
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function char2Stream($conn)
{
    $tableName = 'streams_null_nchar'; 

    // create a test table
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('nchar(512)', 'c2_nchar'),
                     new AE\ColumnMeta('nvarchar(512)', 'c3_nvarchar'),
                     new AE\ColumnMeta('nvarchar(max)', 'c4_nvarchar_max'),
                     new AE\ColumnMeta('ntext', 'c5_ntext'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    sqlsrv_free_stmt($stmt);

    $fname = null;
    $query = "INSERT INTO $tableName (c1_int, c2_nchar, c3_nvarchar, c4_nvarchar_max, c5_ntext) VALUES (?, ?, ?, ?, ?)";
    $res = true;
    if (AE\isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, $query, array(-187518515, &$fname, &$fname, &$fname, &$fname));
        if ($stmt) {
            $res = sqlsrv_execute($stmt);
        }
    } else {
        $stmt = sqlsrv_query($conn, $query, array(-187518515, &$fname, &$fname, &$fname, &$fname));
    }
    if ($stmt === false || !$res) {
        fatalError("Failed in sendQueryStream for $value\n");
    }

    sqlsrv_send_stream_data($stmt);
    sqlsrv_free_stmt($stmt);

    fetchData($conn, $tableName);
    dropTable($conn, $tableName);
}

function fetchData($conn, $tableName)
{
    $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName");
    sqlsrv_execute($stmt);
    $result = sqlsrv_fetch($stmt);
    $numfields = sqlsrv_num_fields($stmt);
    for ($i = 1; $i < $numfields; $i++) {
        $value = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY));
        var_dump($value);
    }
}

echo "\nTest begins...\n";
try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();

    char2Stream($conn);

    sqlsrv_close($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿﻿
Test begins...
NULL
NULL
NULL
NULL

Done
