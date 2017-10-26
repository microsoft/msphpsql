--TEST--
binding parameters, including output parameters, using the simplified syntax.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = connect();

    $stmt = sqlsrv_query($conn, "IF OBJECT_ID('php_table_1_SERIL2', 'U') IS NOT NULL DROP TABLE [php_table_1_SERIL2]");
    if ($stmt !== false) {
        sqlsrv_free_stmt($stmt);
    }

    $stmt = sqlsrv_query($conn, "CREATE TABLE [php_table_1_SERIL2] ([int] int, [varchar] varchar(512)) ");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "INSERT INTO [php_table_1_SERIL2] ([int], [varchar]) VALUES( 1, 'This is a test.' )");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $numRows1 = sqlsrv_rows_affected($stmt);
    if ($numRows1 === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_query($conn, "SELECT * FROM [php_table_1_SERIL2];SELECT * FROM [php_table_1_SERIL2]");

    $row = sqlsrv_fetch_array($stmt);
    if ($row === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $row = sqlsrv_fetch_array($stmt);
    if ($row === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $result = sqlsrv_next_result($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    if ($result === null) {
        fatalError("sqlsrv_next_result returned null");
    }

    $row = sqlsrv_fetch_array($stmt);
    if ($row === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $stmt = sqlsrv_query($conn, "DROP TABLE [php_table_1_SERIL2]");

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo "Test succeeded.\n"
?>
--EXPECT--
Test succeeded.
