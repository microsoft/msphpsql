--TEST--
Populate different binary fields using null stream data as inputs.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function nullBin2String($conn, $tableName)
{
    $fname = null;
    $value = -2106133115;
    $intType = AE\isColEncrypted() ? SQLSRV_SQLTYPE_INT : null;
    $stmt = sqlsrv_query($conn, 
                         "INSERT INTO $tableName (c1_int, c2_varbinary, c3_varbinary_max, c4_image) VALUES (?, ?, ?, ?)", 
                         array(array($value, null, null, $intType),
                               array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(512)), 
                               array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')), 
                               array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_IMAGE)));
    sqlsrv_free_stmt($stmt);

    fetchData($conn, $tableName, $value);
}

function nullPrepBin2String($conn, $tableName)
{
    $fname = null;
    $value = -413736480;
    $stmt = sqlsrv_prepare($conn, 
                           "INSERT INTO $tableName (c1_int, c2_varbinary, c3_varbinary_max, c4_image) VALUES (?, ?, ?, ?)", 
                           array($value, 
                                 array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(512)), 
                                 array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')), 
                                 array(&$fname, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_IMAGE)));
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);

    fetchData($conn, $tableName, $value);
}

function fetchData($conn, $tableName, $value)
{
    if (AE\isColEncrypted()) {
        // bind param when AE is enabled
        $stmt = sqlsrv_prepare($conn, "SELECT * FROM $tableName WHERE c1_int = ?", array($value));
        if ($stmt) {
            sqlsrv_execute($stmt);
        }
    } else {
        $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName WHERE c1_int = $value");
    }
    if (!$stmt) {
        fatalError("Failed in fetch data with value $value\n");
    }
    $result = sqlsrv_fetch($stmt);
    $numfields = sqlsrv_num_fields($stmt);
    for ($i = 1; $i < $numfields; $i++) {
        $value = sqlsrv_get_field($stmt, $i, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        var_dump($value);
    }
}

echo "\nTest begins...\n";
try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();

    // create a test table
    $tableName = 'null_binary_stream';
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('varbinary(512)', 'c2_varbinary'),
                     new AE\ColumnMeta('varbinary(max)', 'c3_varbinary_max'),
                     new AE\ColumnMeta('image', 'c4_image'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table.\n");
    }

    nullBin2String($conn, $tableName);
    nullPrepBin2String($conn, $tableName);
    
    dropTable($conn, $tableName);

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
NULL
NULL

Done
