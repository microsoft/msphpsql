--TEST--
fix for 182741.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = connect();

    if (!$conn) {
        fatalError("Could not connect");
    }

    $stmt = sqlsrv_query($conn, "IF OBJECT_ID('182741', 'U') IS NOT NULL DROP TABLE [182741]");
    if ($stmt !== false) {
        sqlsrv_free_stmt($stmt);
    }

    $stmt = sqlsrv_query($conn, "CREATE TABLE [182741] ([int_type] int, [text_type] text, [ntext_type] ntext, [image_type] image)");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO [182741] ([int_type], [text_type], [ntext_type], [image_type]) VALUES(?, ?, ?, ?)",
                        array( 1, array( "Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR)),
                                  array( "Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR)),
                                  array( "Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)))
    );

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    sqlsrv_query($conn, "DROP TABLE [182741]");

    sqlsrv_free_stmt($stmt);

    sqlsrv_close($conn);

    echo "Test succeeded.";
?>
--EXPECT--
Test succeeded.
