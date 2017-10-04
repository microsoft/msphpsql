--TEST--
maximum size for both nonunicode and unicode data types.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    $conn = connect();
    if (!$conn) {
        die(print_r(sqlsrv_errors(), true));
    }

    dropTable($conn, 'test_max_size');

    $stmt = sqlsrv_query($conn, "CREATE TABLE test_max_size (id int, test_nvarchar nvarchar(4000), test_nchar nchar(4000), test_varchar varchar(8000), test_binary varbinary(8000))");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO test_max_size (id, test_nvarchar, test_nchar) VALUES (?, ?)",
          array( 1, array( "this is a test", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(8000)))
    );
    if ($stmt === false) {
        print_r(sqlsrv_errors());
    } else {
        fatalError("Should have failed (1).");
    }

    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO test_max_size (id, test_nchar) VALUES (?, ?)",
          array( 2, array( "this is a test", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(8000)))
    );
    if ($stmt === false) {
        print_r(sqlsrv_errors());
    } else {
        fatalError("Should have failed (2).");
    }

    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO test_max_size (id, test_varchar) VALUES (?, ?)",
          array( 3, array( "this is a test", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(8000)))
    );
    if ($stmt === false) {
        die(print_r(sqlsrv_errors()));
    }

    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO test_max_size (id, test_binary) VALUES (?, ?)",
          array( 4, array( "this is a test", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(8000)))
    );
    if ($stmt === false) {
        die(print_r(sqlsrv_errors()));
    }

    dropTable($conn, 'test_max_size');

    echo "Test succeeded.\n";
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
            [2] => An invalid size or precision for parameter 2 was specified.
            [message] => An invalid size or precision for parameter 2 was specified.
        )

)
Test succeeded.
