--TEST--
nameless fields return correctly in sqlsrv_fetch_array and sqlsrv_fetch_object.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_ALL);

    require_once("MsCommon.inc");

    $conn = connect();
    if ($conn === false) {
        fatalError("connect failed");
    }

    $stmt = sqlsrv_query($conn, "SELECT COUNT(track) FROM tracks");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    while ($row = sqlsrv_fetch_array($stmt)) {
        print_r($row);
    }

    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "SELECT COUNT(track) FROM tracks");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_object($stmt);
    print_r(sqlsrv_errors(SQLSRV_ERR_WARNINGS));

    sqlsrv_configure('WarningsReturnAsErrors', 1);
    $stmt = sqlsrv_query($conn, "SELECT COUNT(track) FROM tracks");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $row = sqlsrv_fetch_object($stmt);
    if ($row === false) {
        print_r(sqlsrv_errors());
    } else {
        echo "Should have failed since warnings return as errors.\n";
    }

    sqlsrv_close($conn);

?>
--EXPECT--
Array
(
    [0] => 61
    [] => 61
)
Array
(
    [0] => Array
        (
            [0] => 01SSP
            [SQLSTATE] => 01SSP
            [1] => -100
            [code] => -100
            [2] => An empty field name was skipped by sqlsrv_fetch_object.
            [message] => An empty field name was skipped by sqlsrv_fetch_object.
        )

)
Array
(
    [0] => Array
        (
            [0] => 01SSP
            [SQLSTATE] => 01SSP
            [1] => -100
            [code] => -100
            [2] => An empty field name was skipped by sqlsrv_fetch_object.
            [message] => An empty field name was skipped by sqlsrv_fetch_object.
        )

)
