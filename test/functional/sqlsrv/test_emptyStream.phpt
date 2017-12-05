--TEST--
Send an empty stream and null stream test.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', false);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once("MsCommon.inc");

    $conn = AE\connect();

    $tableName = 'test_empty_stream';
    $columns = array(new AE\ColumnMeta('int', 'id'),
                     new AE\ColumnMeta('varchar(max)', 'varchar_stream'),
                     new AE\ColumnMeta('varbinary(max)', 'varbinary_stream'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $f1 = 1;
    $f2 = fopen("data://text/plain,", "r");
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (id, varchar_stream) VALUES (?, ?)", array( &$f1, &$f2 ));
    if ($stmt === false) {
        print_r("sqlsrv_prepare failed.");
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        print_r("sqlsrv_execute(1) failed.");
        die(print_r(sqlsrv_errors(), true));
    }
    fclose($f2);

    $f2 = null;
    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        print_r("sqlsrv_execute(2) failed.");
        die(print_r(sqlsrv_errors(), true));
    }

    $f3 = 1;
    $f4 = fopen("data://text/plain,", "r");
    $stmt = sqlsrv_prepare(
        $conn,
        "INSERT INTO $tableName (id, varbinary_stream) VALUES (?, ?)",
          array( &$f3,
                 array( &$f4, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')) )
    );
    if ($stmt === false) {
        print_r("sqlsrv_prepare failed.");
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        print_r("sqlsrv_execute(3) failed.");
        die(print_r(sqlsrv_errors(), true));
    }
    fclose($f4);

    $f4 = null;
    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        print_r("sqlsrv_execute(4) failed.");
        die(print_r(sqlsrv_errors(), true));
    }

    $stmt = AE\executeQuery($conn, "SELECT id, varchar_stream, varbinary_stream FROM $tableName");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $field = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($field === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    var_dump($field);

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $field = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($field === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    var_dump($field);

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $field = sqlsrv_get_field($stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($field === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    var_dump($field);

    $result = sqlsrv_fetch($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $field = sqlsrv_get_field($stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
    if ($field === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    var_dump($field);

    // test the field range on sqlsrv_get_field
    $field = sqlsrv_get_field($stmt, -2000);
    if ($field !== false) {
        die("sqlsrv_get_field(-2000) should have failed.");
    }
    print_r(sqlsrv_errors());

    dropTable($conn, $tableName);
    sqlsrv_close($conn);
?>
--EXPECT--
string(0) ""
NULL
string(0) ""
NULL
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -14
            [code] => -14
            [2] => An invalid parameter was passed to sqlsrv_get_field.
            [message] => An invalid parameter was passed to sqlsrv_get_field.
        )

)
