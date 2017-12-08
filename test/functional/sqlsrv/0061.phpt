--TEST--
maximum size for both nonunicode and unicode data types.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    $conn = AE\connect();

    $tableName = 'test_max_size';
    $columns = array(new AE\ColumnMeta('int', 'id'),
                     new AE\ColumnMeta('nvarchar(4000)', 'test_nvarchar'),
                     new AE\ColumnMeta('nchar(4000)', 'test_nchar'),
                     new AE\ColumnMeta('varchar(8000)', 'test_varchar'),
                     new AE\ColumnMeta('varbinary(8000)', 'test_binary'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $stmt = AE\executeQueryParams(
        $conn,
        "INSERT INTO $tableName (id, test_nvarchar, test_nchar) VALUES (?, ?)",
        array( 1, array( "this is a test", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NVARCHAR(8000))),
        true,
        "Should have failed (1)."
    );

    $stmt = AE\executeQueryParams(
        $conn,
        "INSERT INTO $tableName (id, test_nchar) VALUES (?, ?)",
          array( 2, array( "this is a test", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_NCHAR(8000))),
          true,
          "Should have failed (2)."
    );

    $stmt = AE\executeQueryParams(
        $conn,
        "INSERT INTO $tableName (id, test_varchar) VALUES (?, ?)",
          array( 3, array( "this is a test", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(8000)))
    );

    $stmt = AE\executeQueryParams(
        $conn,
        "INSERT INTO $tableName (id, test_binary) VALUES (?, ?)",
          array( 4, array( "this is a test", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY(8000)))
    );

    dropTable($conn, '$tableName');

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
