--TEST--
Fix for 168256.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    if (!($conn = connect())) {
        fatalError("Could not connect");
    }

    $tsql = "SELECT OrderQty, UnitPrice FROM [168256]";
    $stmt = sqlsrv_query($conn, $tsql);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    for ($i = 0; $i < 10; $i++) {
        $row = sqlsrv_fetch_array($stmt);
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
