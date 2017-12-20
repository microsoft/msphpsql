--TEST--
Insert with query params but with various invalid inputs or boundaries
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿﻿<?php
require_once('MsCommon.inc');

function getFirstInputParam()
{
    $intType = AE\isColEncrypted() ? SQLSRV_SQLTYPE_INT : null;
    return array(1, null, null, $intType);
}

function minMaxScale($conn)
{
    $tableName = 'minMaxScale';
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('decimal(28,4)', 'c2_decimal'),
                     new AE\ColumnMeta('numeric(32,4)', 'c3_numeric'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_decimal) VALUES (?, ?)", array(getFirstInputParam(), array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_DECIMAL(28, 34))));
    printErrors();

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c3_numeric) VALUES (?, ?)", array(getFirstInputParam(), array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NUMERIC(32, -1))));
    printErrors();
    dropTable($conn, $tableName);
}

function minMaxSize($conn)
{
    $tableName = 'minMaxSize';
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('varchar(max)', 'c2_varchar_max'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(getFirstInputParam(), array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(0))));
    printErrors();

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(getFirstInputParam(), array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(9000))));
    printErrors();
    dropTable($conn, $tableName);
}

function minMaxPrecision($conn)
{
    $tableName = 'minMaxPrecision';
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('decimal(28,4)', 'c2_decimal'),
                     new AE\ColumnMeta('numeric(32,4)', 'c3_numeric'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c3_numeric) VALUES (?, ?)", array(getFirstInputParam(), array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NUMERIC(40, 0))));
    printErrors();

    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_decimal) VALUES (?, ?)", array(getFirstInputParam(), array(0.0, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_DECIMAL(-1, 0))));
    printErrors();
    dropTable($conn, $tableName);
}

echo "\nTest begins...\n";
try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();

    minMaxScale($conn);
    minMaxSize($conn);
    minMaxPrecision($conn);

    sqlsrv_close($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿﻿
Test begins...
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.
An invalid size or precision for parameter 2 was specified.

Done
