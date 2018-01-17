--TEST--
binding streams using full syntax.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    
    $conn = AE\connect();
    $tableName = 'test_params';
    $columns = array(new AE\ColumnMeta('tinyint', 'id'),
                     new AE\ColumnMeta('char(10)', 'name'),
                     new AE\ColumnMeta('float', 'double'),
                     new AE\ColumnMeta('varbinary(max)', 'stuff'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    
    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");

    $insertSql = "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)";
    // use full details
    $stmt = sqlsrv_query(
        $conn, 
        $insertSql,
        array(
            array(&$f1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_TINYINT),
            array(&$f2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_CHAR(10)),
            array(&$f3, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_FLOAT, SQLSRV_SQLTYPE_FLOAT),
            array(&$f4, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max')))
    );
    if ($stmt === false) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_query(1) failed.");
    }
    while ($success = sqlsrv_send_stream_data($stmt)) {
    }
    if (!is_null($success)) {
        print_r(sqlsrv_errors());
        sqlsrv_cancel($stmt);
        sqlsrv_free_stmt($stmt);
        die("sqlsrv_send_stream_data(1) failed.");
    }

    fclose($f4);
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    // use nulls for php types
    $stmt = sqlsrv_query(
        $conn,
        $insertSql,
        array(
            array(&$f1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_TINYINT),
            array(&$f2, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_CHAR(10)),
            array(&$f3, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
            array(&$f4, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARBINARY('max')))
    );
    if ($stmt !== false) {
        die("sqlsrv_query(2) should have failed.");
    } else {
        $error = sqlsrv_errors()[0];
        verifyError($error, '22018', 'Invalid character value for cast specification');
    }
    print_r("sqlsrv_query(2) failed.\n");
    fclose($f4);

    // try it with nothing but default values
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    // use nulls for php types
    $stmt = sqlsrv_query(
        $conn,
        $insertSql,
        array(
            array(&$f1, null, null, null),
            array(&$f2, null, null, null),
            array(&$f3, null, null, null),
            array(&$f4, null, null, null))
    );
    
    $AEQueryError = 'Must specify the SQL type for each parameter in a parameterized query when using sqlsrv_query in a column encryption enabled connection.';
    if ($stmt === false) {
        $error = sqlsrv_errors()[0];
        if (AE\isColEncrypted()) {
            // When AE is enabled, the error message will be about sqlsrv_query and 
            // parameterized query
            verifyError($error, 'IMSSP', $AEQueryError);
        } else {
            verifyError($error, '42000', 'Implicit conversion from data type varchar(max) to varbinary(max) is not allowed. Use the CONVERT function to run this query.');
        }
        print_r("sqlsrv_query(3) failed.\n");
    } else {
        sqlsrv_free_stmt($stmt);
        die("sqlsrv_query(3) shouldn't have succeeded.");
    }

    // print out the results for comparison
    $stmt = sqlsrv_query($conn, "SELECT id, [double], name, stuff FROM test_params");
    if ($stmt === false) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_execute failed.");
    }
    while (sqlsrv_fetch($stmt)) {
        $id = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$id\n";
        $double = sqlsrv_get_field($stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$double\n";
        $name = sqlsrv_get_field($stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$name\n";
        $stream = sqlsrv_get_field($stmt, 3, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        if (!$stream) {
            fatalError('Fetching data stream failed!');
        } else {
            while (!feof($stream)) {
                $str = fread($stream, 10000);
                if ($str !== "This is some text meant to test binding parameters to streams") {
                    fatalError("Incorrect data: \'$str\'!\n");
                }
            }
        }
        echo "\n";
    }
    sqlsrv_free_stmt($stmt);
    fclose($f4);

    // try it with nothing but default values
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    // use nulls for php types
    $stmt = sqlsrv_query(
        $conn,
        $insertSql,
        array(
            array(&$f1, null, null, null),
            array(),
            array(&$f3, null, null, null),
            array(&$f4, null, null, null))
    );
    if ($stmt !== false) {
        die("sqlsrv_query should have failed.");
    }

    $error = sqlsrv_errors()[0];
    if (AE\isColEncrypted()) {
        // When AE is enabled, the error message will be about sqlsrv_query and 
        // parameterized query
        verifyError($error, 'IMSSP', $AEQueryError);
    } else {
        verifyError($error, 'IMSSP', 'Parameter array 2 must have at least one value or variable.');
    }

    fclose($f4);
    echo "Done\n";

    dropTable($conn, $tableName);
    sqlsrv_close($conn);
?>
--EXPECT--
sqlsrv_query(2) failed.
sqlsrv_query(3) failed.
1
12.0
testtestte

Done