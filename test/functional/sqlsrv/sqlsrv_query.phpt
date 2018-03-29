--TEST--
sqlsrv_query test. Performs same tasks as 0006.phpt, using sqlsrv_query.
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
                     new AE\ColumnMeta('varchar(max)', 'stuff'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $insertSql = "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)";
    
    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    $stmt = AE\executeQueryParams($conn, $insertSql, array( $f1, $f2, $f3, $f4 ), false, "sqlsrv_query(1) failed.");
    while ($success = sqlsrv_send_stream_data($stmt)) {
    }
    if (!is_null($success)) {
        sqlsrv_cancel($stmt);
        sqlsrv_free_stmt($stmt);
        die("sqlsrv_send_stream_data failed.");
    }

    $stmt = sqlsrv_query($conn, "SELECT id, [double], name, stuff FROM test_params");
    if (!$stmt) {
        fatalError("sqlsrv_query(2) failed.");
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
                $str = fread($stream, 4000);
                if ($str !== "This is some text meant to test binding parameters to streams") {
                    fatalError("Incorrect data: \'$str\'!\n");
                }
            }
        }
        echo "\n";
    }

    sqlsrv_query($conn, "DROP TABLE test_params");
    dropTable($conn, $tableName);

    sqlsrv_close($conn);

?>
--EXPECT--
1
12.0
testtestte

