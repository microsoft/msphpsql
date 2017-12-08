--TEST--
Insert with query params but with wrong parameters or types
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function createTableForTesting($conn, $tableName)
{
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('varchar(max)', 'c2_varchar_max'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    
    sqlsrv_free_stmt($stmt);
}

function getFirstInputParam()
{
    $intType = AE\isColEncrypted() ? SQLSRV_SQLTYPE_INT : null;
    return array(1, null, null, $intType);
}

function phpTypeMismatch($conn)
{
    $tableName = 'phpTypeMismatch';
    createTableForTesting($conn, $tableName);
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(getFirstInputParam(), array(1.5, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_FLOAT, SQLSRV_SQLTYPE_VARCHAR('max'))));
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    $result = sqlsrv_fetch($stmt);
    if (! $result) {
        echo "Fetch should succeed\n";
    }
    $value0 = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    $value1 = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($value0 != "1" || $value1 != "1.5") {
        echo "Data $value0 or $value1 unexpected\n";
    }
    
    dropTable($conn, $tableName);
}

function dirInvalid($conn)
{
    $tableName = 'dirInvalid';
    createTableForTesting($conn, $tableName);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(getFirstInputParam(), array("Test Data", 32, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR('max'))));
    printErrors();

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(getFirstInputParam(), array("Test Data", 'SQLSRV_PARAM_INTERNAL', SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR('max'))));

    printErrors();
    dropTable($conn, $tableName);
}

function phpTypeEncoding($conn)
{
    $tableName = 'phpTypeEncoding';
    createTableForTesting($conn, $tableName);

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(getFirstInputParam(), array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('SQLSRV_ENC_UNKNOWN'), null)));

    printErrors();
    dropTable($conn, $tableName);
}

function phpTypeInvalid($conn)
{
    $tableName = 'phpTypeInvalid';
    createTableForTesting($conn, $tableName);
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(getFirstInputParam(), array("Test Data", SQLSRV_PARAM_IN, 'SQLSRV_PHPTYPE_UNKNOWN', SQLSRV_SQLTYPE_VARCHAR('max'))));
    printErrors();

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(getFirstInputParam(), array("Test Data", SQLSRV_PARAM_IN, 6, SQLSRV_SQLTYPE_VARCHAR('max'))));

    printErrors();
    dropTable($conn, $tableName);
}

echo "\nTest begins...\n";

try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();

    phpTypeMismatch($conn);
    dirInvalid($conn);
    phpTypeEncoding($conn);
    phpTypeInvalid($conn);

    sqlsrv_close($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";
    
?>
--EXPECT--
﻿﻿
Test begins...
An invalid direction for parameter 2 was specified. SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, and SQLSRV_PARAM_INOUT are valid values.
An invalid direction for parameter 2 was specified. SQLSRV_PARAM_IN, SQLSRV_PARAM_OUT, and SQLSRV_PARAM_INOUT are valid values.
An invalid PHP type for parameter 2 was specified.
An invalid PHP type for parameter 2 was specified.
An invalid PHP type for parameter 2 was specified.

Done
