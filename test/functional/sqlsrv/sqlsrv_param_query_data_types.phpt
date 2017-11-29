--TEST--
Test insert various numeric data types and fetch them back as strings
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿<?php
require_once('MsCommon.inc');
require_once('tools.inc');

function paramQuery($conn, $type, $sqlsrvType, $inValue)
{
    $tableName = 'param_test';

    $columns = array(new AE\ColumnMeta('int', 'col1'),
                     new AE\ColumnMeta($type, 'col2'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $insertSql = "INSERT INTO [$tableName] VALUES (?, ?)";
    $params = array(1, array($inValue, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_FLOAT, $sqlsrvType));
    if (AE\isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, $insertSql, $params);
        if ($stmt) {
            sqlsrv_execute($stmt);
        }
    } else {
        $stmt = sqlsrv_query($conn, $insertSql, $params);
    }
    sqlsrv_free_stmt($stmt);
    
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    sqlsrv_fetch($stmt);
    $value = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));

    compareNumericData($value, $inValue);

    dropTable($conn, $tableName);
    sqlsrv_free_stmt($stmt);
}

echo "\nTest begins...\n";
try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();

    paramQuery($conn, "float", SQLSRV_SQLTYPE_FLOAT, 12.345);
    paramQuery($conn, "money", SQLSRV_SQLTYPE_MONEY, 56.78);
    paramQuery($conn, "numeric(32,4)", SQLSRV_SQLTYPE_NUMERIC(32, 4), 12.34);
    paramQuery($conn, "real", SQLSRV_SQLTYPE_REAL, 98.760);
    paramQuery($conn, "smallmoney", SQLSRV_SQLTYPE_SMALLMONEY, 56.78);

    sqlsrv_close($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿
Test begins...

Done
