--TEST--
sqlsrv_query test.  Performs same tasks as 0006.phpt, using sqlsrv_query.
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

    $stmt = sqlsrv_query($conn, "IF OBJECT_ID('test_params', 'U') IS NOT NULL DROP TABLE test_params");
    if (!$stmt) {
        $errors = sqlsrv_errors();
        if ($errors[0]["SQLSTATE"] != "42S02") {
            var_dump($errors);
            die("sqlsrv_query(3) failed.");
        }
    }

    $stmt = sqlsrv_query($conn, "CREATE TABLE test_params (id tinyint, name char(10), [double] float, stuff varchar(max))");
    if (!$stmt) {
        fatalError("sqlsrv_query(4) failed.");
    }

    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    $stmt = sqlsrv_query(
        $conn,
        "INSERT INTO test_params (id, name, [double], stuff) VALUES (?, ?, ?, ?)",
                                    array( $f1, $f2, $f3, $f4 )
    );
    if (!$stmt) {
        fatalError("sqlsrv_query(5) failed.");
    }
    while ($success = sqlsrv_send_stream_data($stmt)) {
    }
    if (!is_null($success)) {
        sqlsrv_cancel($stmt);
        sqlsrv_free_stmt($stmt);
        die("sqlsrv_send_stream_data failed.");
    }

    $stmt = sqlsrv_query($conn, "SELECT id, [double], name, stuff FROM test_params");
    if (!$stmt) {
        fatalError("sqlsrv_query(6) failed.");
    }

    while (sqlsrv_fetch($stmt)) {
        $id = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$id <br/>";
        $double = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$double <br/>";
        $name = sqlsrv_get_field($stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$name <br/>";
        $stream = sqlsrv_get_field($stmt, 3, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        while (!feof($stream)) {
            $str = fread($stream, 4000);
            echo $str;
        }
        echo "<br/>";
    }

    sqlsrv_query($conn, "DROP TABLE test_params");

    sqlsrv_close($conn);

?>
--EXPECTF--
1 <br/>12.0 <br/>testtestte <br/>This is some text meant to test binding parameters to streams<br/>%A
