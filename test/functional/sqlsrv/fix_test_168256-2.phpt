--TEST--
Fix for 168256.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    $connectionInfo = array( "Database"=>"test");
    if (!($conn = connect())) {
        fatalError("Could not connect");
    }

    $tsql = "SELECT OrderQty, UnitPrice FROM [168256]";

    // default fetch_array (both)
    $stmt = sqlsrv_query($conn, $tsql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    for ($i = 0; $i < 10; $i++) {
        $row = sqlsrv_fetch_array($stmt);
        print_r($row);
    }

    sqlsrv_free_stmt($stmt);

    // fetch array with numeric indices
    $stmt = sqlsrv_query($conn, $tsql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    for ($i = 0; $i < 10; $i++) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
        print_r($row);
    }

    sqlsrv_free_stmt($stmt);

    // fetch array with name indices
    $stmt = sqlsrv_query($conn, $tsql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    for ($i = 0; $i < 10; $i++) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
        print_r($row);
    }

    sqlsrv_free_stmt($stmt);

    // fetch array with both indices
    $stmt = sqlsrv_query($conn, $tsql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    for ($i = 0; $i < 10; $i++) {
        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_BOTH);
        print_r($row);
    }

    sqlsrv_free_stmt($stmt);

    sqlsrv_close($conn);
?>
--EXPECT--
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2024.9940
    [UnitPrice] => 2024.9940
)
Array
(
    [0] => 3
    [OrderQty] => 3
    [1] => 2024.9940
    [UnitPrice] => 2024.9940
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2024.9940
    [UnitPrice] => 2024.9940
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2039.9940
    [UnitPrice] => 2039.9940
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2039.9940
    [UnitPrice] => 2039.9940
)
Array
(
    [0] => 2
    [OrderQty] => 2
    [1] => 2039.9940
    [UnitPrice] => 2039.9940
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2039.9940
    [UnitPrice] => 2039.9940
)
Array
(
    [0] => 3
    [OrderQty] => 3
    [1] => 28.8404
    [UnitPrice] => 28.8404
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 28.8404
    [UnitPrice] => 28.8404
)
Array
(
    [0] => 6
    [OrderQty] => 6
    [1] => 5.7000
    [UnitPrice] => 5.7000
)
Array
(
    [0] => 1
    [1] => 2024.9940
)
Array
(
    [0] => 3
    [1] => 2024.9940
)
Array
(
    [0] => 1
    [1] => 2024.9940
)
Array
(
    [0] => 1
    [1] => 2039.9940
)
Array
(
    [0] => 1
    [1] => 2039.9940
)
Array
(
    [0] => 2
    [1] => 2039.9940
)
Array
(
    [0] => 1
    [1] => 2039.9940
)
Array
(
    [0] => 3
    [1] => 28.8404
)
Array
(
    [0] => 1
    [1] => 28.8404
)
Array
(
    [0] => 6
    [1] => 5.7000
)
Array
(
    [OrderQty] => 1
    [UnitPrice] => 2024.9940
)
Array
(
    [OrderQty] => 3
    [UnitPrice] => 2024.9940
)
Array
(
    [OrderQty] => 1
    [UnitPrice] => 2024.9940
)
Array
(
    [OrderQty] => 1
    [UnitPrice] => 2039.9940
)
Array
(
    [OrderQty] => 1
    [UnitPrice] => 2039.9940
)
Array
(
    [OrderQty] => 2
    [UnitPrice] => 2039.9940
)
Array
(
    [OrderQty] => 1
    [UnitPrice] => 2039.9940
)
Array
(
    [OrderQty] => 3
    [UnitPrice] => 28.8404
)
Array
(
    [OrderQty] => 1
    [UnitPrice] => 28.8404
)
Array
(
    [OrderQty] => 6
    [UnitPrice] => 5.7000
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2024.9940
    [UnitPrice] => 2024.9940
)
Array
(
    [0] => 3
    [OrderQty] => 3
    [1] => 2024.9940
    [UnitPrice] => 2024.9940
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2024.9940
    [UnitPrice] => 2024.9940
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2039.9940
    [UnitPrice] => 2039.9940
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2039.9940
    [UnitPrice] => 2039.9940
)
Array
(
    [0] => 2
    [OrderQty] => 2
    [1] => 2039.9940
    [UnitPrice] => 2039.9940
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 2039.9940
    [UnitPrice] => 2039.9940
)
Array
(
    [0] => 3
    [OrderQty] => 3
    [1] => 28.8404
    [UnitPrice] => 28.8404
)
Array
(
    [0] => 1
    [OrderQty] => 1
    [1] => 28.8404
    [UnitPrice] => 28.8404
)
Array
(
    [0] => 6
    [OrderQty] => 6
    [1] => 5.7000
    [UnitPrice] => 5.7000
)
