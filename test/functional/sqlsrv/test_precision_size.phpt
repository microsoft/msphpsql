--TEST--
invalid precision and sizes for parameters.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once("MsCommon.inc");

    $conn = connect();
    if (!$conn) {
        fatalError("sqlsrv_create failed.");
    }

    $stmt = sqlsrv_prepare($conn, "IF OBJECT_ID('test_precision_size', 'U') IS NOT NULL DROP TABLE test_precision_size");
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_prepare($conn, "CREATE TABLE test_precision_size (id tinyint, varchar_type varchar(8000), decimal_type decimal(38,19)");
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);

    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;

    // test an invalid size for a varchar field (8000 max)
    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO test_precision_size (id, varchar_type, decimal_type ) VALUES (?, ?, ?)",
        array( $f1, array( $f2, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(9000)), $f3 )
    );
    if ($stmt !== false) {
        die("sqlsrv_query should have failed.");
    } else {
        print_r(sqlsrv_errors());
    }

    // test an invalid precision where precision > than max allowed (38)
    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO test_precision_size (id, varchar_type, decimal_type ) VALUES (?, ?, ?)",
        array( $f1, array( $f2, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(8000)), array( $f3, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(40, 0)))
    );
    if ($stmt !== false) {
        die("sqlsrv_query should have failed.");
    } else {
        print_r(sqlsrv_errors());
    }

    // test an invalid precision where the scale > precision
    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO test_precision_size (id, varchar_type, decimal_type ) VALUES (?, ?, ?)",
        array( $f1, array( $f2, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(8000)), array( $f3, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_DECIMAL(15, 30)))
    );
    if ($stmt !== false) {
        die("sqlsrv_query should have failed.");
    } else {
        print_r(sqlsrv_errors());
    }

    sqlsrv_query($conn, "DROP TABLE test_precision_size");

    sqlsrv_close($conn);
?>
--EXPECT--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -31
            [code] => -31
            [2] => An invalid size or precision for parameter 2 was specified.
            [message] => An invalid size or precision for parameter 2 was specified.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -31
            [code] => -31
            [2] => An invalid size or precision for parameter 3 was specified.
            [message] => An invalid size or precision for parameter 3 was specified.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -31
            [code] => -31
            [2] => An invalid size or precision for parameter 3 was specified.
            [message] => An invalid size or precision for parameter 3 was specified.
        )

)
