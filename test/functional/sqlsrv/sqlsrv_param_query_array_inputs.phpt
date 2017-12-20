--TEST--
Test insert various numeric data types and fetch them back as strings
--FILE--
﻿<?php
require_once('MsCommon.inc');

function execDataValue($conn, $numRows, $phpType = SQLSRV_PHPTYPE_NULL)
{
    $tableName = 'param_query_value';
    $columns = array(new AE\ColumnMeta('int', 'c1_int'),
                     new AE\ColumnMeta('smallint', 'c2_smallint'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("failed to create table $tableName\n");
    }
    if ($phpType == SQLSRV_PHPTYPE_NULL) {
        echo "Insert integers without PHP type\n";
        $stmt = sqlsrv_prepare($conn, "INSERT INTO [$tableName] (c1_int, c2_smallint) VALUES (?, ?)", array(array(&$v1), array(&$v2)));
    } else { // SQLSRV_PHPTYPE_INT
        echo "Insert integers as SQLSRV_PHPTYPE_INT\n";
        $stmt = sqlsrv_prepare($conn, "INSERT INTO [$tableName] (c1_int, c2_smallint) VALUES (?, ?)", array(array(&$v1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT), array(&$v2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT)));
    }
    if (!$stmt) {
        fatalError("execDataValue: failed to prepare statement!");
    }

    $value = 1;
    for ($i = 0; $i < $numRows; $i++) {
        $v1 = $value;
        $v2 = $v1 + 1;
        $res = sqlsrv_execute($stmt);
        if (!$res) {
            fatalError("execDataValue: failed to insert $v1, $v2");
        }

        $value += 10;
    }

    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    fetchData($stmt, $numRows);

    sqlsrv_free_stmt($stmt);
    
    dropTable($conn, $tableName);
}

function execDataParam($conn, $numRows, $withParam = false)
{
    $tableName = 'param_query_param';
    $columns = array(new AE\ColumnMeta('float', 'c1_float'),
                     new AE\ColumnMeta('real', 'c2_real'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("failed to create table $tableName\n");
    }

    if ($withParam) {
        echo "Insert floats with direction specified\n";
        $stmt = sqlsrv_prepare($conn, "INSERT INTO [$tableName] (c1_float, c2_real) VALUES (?, ?)", array(array(&$v1, SQLSRV_PARAM_IN), array(&$v2, SQLSRV_PARAM_IN)));
    } else { // no param
        echo "Insert floats without direction\n";
        $stmt = sqlsrv_prepare($conn, "INSERT INTO [$tableName] (c1_float, c2_real) VALUES (?, ?)", array(&$v1, &$v2));
    }
    if (!$stmt) {
        fatalError("execDataParam: failed to prepare statement!");
    }

    $value = 1.0;
    for ($i = 0; $i < $numRows; $i++) {
        $v1 = $value;
        $v2 = $v1 + 1.0;
        $res = sqlsrv_execute($stmt);
        if (!$res) {
            fatalError("execDataParam: failed to insert $v1, $v2");
        }

        $value += 10;
    }

    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");
    fetchData($stmt, $numRows);

    sqlsrv_free_stmt($stmt);

    dropTable($conn, $tableName);
}

function fetchData($stmt, $numRows)
{
    for ($i = 0; $i < $numRows; $i++) {
        sqlsrv_fetch($stmt);

        $value = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$value, ";

        $value = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$value\n";
    }
}

echo "\nTest begins...\n";
try {
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();

    $numRows = 5;

    execDataValue($conn, $numRows);
    execDataValue($conn, $numRows, SQLSRV_PHPTYPE_INT);
    execDataParam($conn, $numRows, true);
    execDataParam($conn, $numRows);

    sqlsrv_close($conn);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿
Test begins...
Insert integers without PHP type
1, 2
11, 12
21, 22
31, 32
41, 42
Insert integers as SQLSRV_PHPTYPE_INT
1, 2
11, 12
21, 22
31, 32
41, 42
Insert floats with direction specified
1.0, 2.0
11.0, 12.0
21.0, 22.0
31.0, 32.0
41.0, 42.0
Insert floats without direction
1.0, 2.0
11.0, 12.0
21.0, 22.0
31.0, 32.0
41.0, 42.0

Done
