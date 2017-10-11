--TEST--
Test sqlsrv_num_rows method.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    $conn = connect();

    if (!$conn) {
        fatalError("Failed to connect.");
    }

    dropTable($conn, 'utf16invalid');
    $stmt = sqlsrv_query($conn, "CREATE TABLE utf16invalid (id int identity, c1 nvarchar(100))");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $stmt = sqlsrv_query($conn, "INSERT INTO utf16invalid (c1) VALUES ('TEST')");
    if ($stmt === false) {
        die(print_r(sqlsrv_errors()));
    }
    $stmt = sqlsrv_query($conn, "SELECT * FROM utf16invalid", array(), array("Scrollable" => SQLSRV_CURSOR_KEYSET ));
    $row_nums = sqlsrv_num_rows($stmt);

    echo $row_nums;

    dropTable($conn, 'utf16invalid');

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
?>

--EXPECT--
1
