--TEST--
make sure errors are cleared for each new API call
--DESCRIPTION--
make sure errors are cleared for each new API call
invalid parameters are reported via sqlsrv_errors, and 
sqlsrv_close returns true even if an error happens. 
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require('MsCommon.inc');

    $conn = sqlsrv_connect("InvalidServerName", array( "Database" => "test" ));
    $result = sqlsrv_close($conn);
    $errors = sqlsrv_errors();
    if ($result !== false) {
        die("sqlsrv_close succeeded despite an invalid server name.");
    }
    print_r($errors);
    
    $conn = AE\connect();
    $tableName = 'test_params';
    $columns = array(new AE\ColumnMeta('tinyint', 'id'),
                     new AE\ColumnMeta('char(10)', 'name'),
                     new AE\ColumnMeta('float', 'double'),
                     new AE\ColumnMeta('varchar(max)', 'stuff'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    sqlsrv_free_stmt($stmt);

    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");

    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, "testtestte", &$f3, &$f4 ));
    if (!$stmt) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_prepare failed.");
    }

    $success = sqlsrv_execute($stmt);
    if (!$success) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_execute failed.");
    }
    while ($success = sqlsrv_send_stream_data($stmt)) {
    }
    if (!is_null($success)) {
        sqlsrv_cancel($stmt);
        sqlsrv_free_stmt($stmt);
        die("sqlsrv_send_stream_data failed.");
    }

    $f1 = 2;
    $f3 = 13.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20more%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    $success = sqlsrv_execute($stmt);
    if (!$success) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_execute failed.");
    }
    while ($success = sqlsrv_send_stream_data($stmt)) {
    }
    if (!is_null($success)) {
        sqlsrv_cancel($stmt);
        sqlsrv_free_stmt($stmt);
        die("sqlsrv_send_stream_data failed.");
    }

    $result = sqlsrv_free_stmt($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_free_stmt($stmt);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_free_stmt(null);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_free_stmt($conn);
    if ($result !== false) {
        die("sqlsrv_free_stmt shouldn't have freed the connection resource");
    }
    print_r(sqlsrv_errors());
    $result = sqlsrv_free_stmt(1);
    if ($result !== false) {
        die("sqlsrv_free_stmt shouldn't have freed a 1");
    }
    print_r(sqlsrv_errors());

    dropTable($conn, $tableName);

    $result = sqlsrv_close($conn);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_close($conn);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_close(null);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $result = sqlsrv_close(1);
    if ($result !== false) {
        die("sqlsrv_close shouldn't have freed a 1");
    }
    print_r(sqlsrv_errors());

    echo "Test successfully done.\n";
?>
--EXPECTF--
Warning: sqlsrv_close() expects parameter 1 to be resource, boolean given in %Ssqlsrv_errors.php on line %x
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -14
            [code] => -14
            [2] => An invalid parameter was passed to sqlsrv_close.
            [message] => An invalid parameter was passed to sqlsrv_close.
        )

)

Warning: sqlsrv_free_stmt(): supplied resource is not a valid ss_sqlsrv_stmt resource in %Ssqlsrv_errors.php on line %x

Warning: sqlsrv_free_stmt() expects parameter 1 to be resource, null given in %Ssqlsrv_errors.php on line %x

Warning: sqlsrv_free_stmt(): supplied resource is not a valid ss_sqlsrv_stmt resource in %Ssqlsrv_errors.php on line %x
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -14
            [code] => -14
            [2] => An invalid parameter was passed to sqlsrv_free_stmt.
            [message] => An invalid parameter was passed to sqlsrv_free_stmt.
        )

)

Warning: sqlsrv_free_stmt() expects parameter 1 to be resource, integer given in %Ssqlsrv_errors.php on line %x
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -14
            [code] => -14
            [2] => An invalid parameter was passed to sqlsrv_free_stmt.
            [message] => An invalid parameter was passed to sqlsrv_free_stmt.
        )

)

Warning: sqlsrv_close(): supplied resource is not a valid ss_sqlsrv_conn resource in %Ssqlsrv_errors.php on line %x

Warning: sqlsrv_close() expects parameter 1 to be resource, null given in %Ssqlsrv_errors.php on line %x

Warning: sqlsrv_close() expects parameter 1 to be resource, integer given in %Ssqlsrv_errors.php on line %x
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -14
            [code] => -14
            [2] => An invalid parameter was passed to sqlsrv_close.
            [message] => An invalid parameter was passed to sqlsrv_close.
        )

)
Test successfully done.
