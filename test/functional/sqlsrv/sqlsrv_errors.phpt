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
    // When testing with PHP 8.0 it throws a TypeError instead of a warning. Thus implement a custom 
    // warning handler such that with PHP 7.x the warning would be handled to throw a TypeError.
    // Sometimes the error messages from PHP 8.0 may be different and have to be handled differently.
    function warningHandler($errno, $errstr) 
    { 
        throw new TypeError($errstr);
    }
    
    function compareMessages($err, $exp8x, $exp7x) 
    {
        $expected = (PHP_MAJOR_VERSION == 8) ? $exp8x : $exp7x;
        if (!fnmatch($expected, $err->getMessage())) {
            echo $err->getMessage() . PHP_EOL;
        }
    }

    set_error_handler("warningHandler", E_WARNING);
       
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require('MsCommon.inc');

    $conn = sqlsrv_connect("InvalidServerName", array( "Database" => "test" ));
    try {
        $result = sqlsrv_close($conn);
        if ($result !== false) {
            die("sqlsrv_close succeeded despite an invalid server name.");
        }
    } catch (TypeError $e) {
        compareMessages($e, 
                        "sqlsrv_close(): Argument #1 (\$conn) must be of type resource, bool given", 
                        "sqlsrv_close() expects parameter 1 to be resource, bool* given");       
    }
    
    $errors = sqlsrv_errors();
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
    try {
        $result = sqlsrv_free_stmt($stmt);
        if ($result === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    try {
        $result = sqlsrv_free_stmt(null);
        if ($result === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } catch (TypeError $e) {
        compareMessages($e, 
                    "sqlsrv_free_stmt(): Argument #1 (\$stmt) must be of type resource, null given", 
                    "sqlsrv_free_stmt() expects parameter 1 to be resource, null given");       
    }
    
    try {
        $result = sqlsrv_free_stmt($conn);
        if ($result !== false) {
            die("sqlsrv_free_stmt shouldn't have freed the connection resource");
        }
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    print_r(sqlsrv_errors());
    
    try {
        $result = sqlsrv_free_stmt(1);
        if ($result !== false) {
            die("sqlsrv_free_stmt shouldn't have freed a 1");
        }
    } catch (TypeError $e) {
        compareMessages($e, 
                    "sqlsrv_free_stmt(): Argument #1 (\$stmt) must be of type resource, int given", 
                    "sqlsrv_free_stmt() expects parameter 1 to be resource, int* given");       
    }

    print_r(sqlsrv_errors());

    dropTable($conn, $tableName);

    $result = sqlsrv_close($conn);
    if ($result === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    try {
        $result = sqlsrv_close($conn);
        if ($result === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    try {
        $result = sqlsrv_close(null);
        if ($result === false) {
            die(print_r(sqlsrv_errors(), true));
        }
    } catch (TypeError $e) {
        compareMessages($e, 
                    "sqlsrv_close(): Argument #1 (\$conn) must be of type resource, null given", 
                    "sqlsrv_close() expects parameter 1 to be resource, null given");       
    }

    try {
        $result = sqlsrv_close(1);
        if ($result !== false) {
            die("sqlsrv_close shouldn't have freed a 1");
        }
    } catch (TypeError $e) {
        compareMessages($e, 
                    "sqlsrv_close(): Argument #1 (\$conn) must be of type resource, int given", 
                    "sqlsrv_close() expects parameter 1 to be resource, int* given");       
    }

    print_r(sqlsrv_errors());

    echo "Test successfully done.\n";
?>
--EXPECT--
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
sqlsrv_free_stmt(): supplied resource is not a valid ss_sqlsrv_stmt resource
sqlsrv_free_stmt(): supplied resource is not a valid ss_sqlsrv_stmt resource
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
sqlsrv_close(): supplied resource is not a valid ss_sqlsrv_conn resource
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
