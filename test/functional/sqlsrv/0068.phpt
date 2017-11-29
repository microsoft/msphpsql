--TEST--
warnings for non reference variables.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', false);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
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
    $stmt = sqlsrv_prepare($conn, "INSERT INTO test_empty_stream (id, varchar_stream, varbinary_stream) VALUES (?, ?, ?)", array($f1, array( $f2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM('binary'), SQLSRV_SQLTYPE_VARCHAR('max')),
          fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r")));
    if ($stmt === false) {
        print_r("sqlsrv_prepare failed.");
        print_r(sqlsrv_errors());
    }
    $result = sqlsrv_execute($stmt);
    if ($result !== false) {
        fatalError("Expected sqlsrv_execute to fail!\n");
    } else {
        echo "sqlsrv_execute failed\n";

        // verify the error contents
        $error = sqlsrv_errors()[0];
        if (AE\isColEncrypted()) {
            // When AE is enabled, implicit conversion will not take place
            verifyError($error, '22018', 'Invalid character value for cast specification');
        } else {
            verifyError($error, '42000', 'Implicit conversion from data type varchar(max) to varbinary(max) is not allowed. Use the CONVERT function to run this query.');
        }
    }

    echo "Done\n";
    
    dropTable($conn, $tableName);
    sqlsrv_close($conn);
?>
--EXPECT--
sqlsrv_execute failed
Done
