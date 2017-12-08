--TEST--
sqlsrv_stmt_rows_affected.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
    sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);

    require_once('MsCommon.inc');

    $conn = AE\connect();
    $tableName = 'test_params';
    $columns = array(new AE\ColumnMeta('tinyint', 'id'),
                     new AE\ColumnMeta('char(10)', 'name'),
                     new AE\ColumnMeta('float', 'double'),
                     new AE\ColumnMeta('varchar(4000)', 'stuff'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, &$f2, &$f3, &$f4 ));
    if (!$stmt) {
        fatalError("sqlsrv_prepare(4) failed.");
    }

    for ($record = 1; $record <= 4; ++$record) {
        $success = sqlsrv_execute($stmt);
        if (!$success) {
            fatalError("sqlsrv_execute($record) failed.");
        }
        while ($success = sqlsrv_send_stream_data($stmt)) {
        }
        if (!is_null($success)) {
            sqlsrv_cancel($stmt);
            sqlsrv_free_stmt($stmt);
            die("sqlsrv_send_stream_data failed.");
        }
        $row_count = sqlsrv_rows_affected($stmt);
        if ($row_count != 1) {
            if ($row_count == -1) {
                var_dump(sqlsrv_errors());
            }
            die("sqlsrv_rows_returned $row_count instead of 1");
        }
        echo "rows = $row_count<br/>\n";
    }
    sqlsrv_free_stmt($stmt);

    if (AE\isColEncrypted()) {
        $stmt = sqlsrv_prepare($conn, "UPDATE $tableName SET [double] = ? FROM $tableName WHERE [double] = ?", array(13.0, 12.0));
    } else {
        $stmt = sqlsrv_prepare($conn, "UPDATE $tableName SET [double] = 13.0 FROM $tableName WHERE [double] = 12.0");
    }
    if (!$stmt) {
        fatalError("sqlsrv_prepare(2) failed.");
    }
    $success = sqlsrv_execute($stmt);
    if (!$success) {
        fatalError("sqlsrv_execute(5) failed.");
    }
    $row_count = sqlsrv_rows_affected($stmt);
    if ($row_count != 4) {
        if ($row_count == -1) {
            var_dump(sqlsrv_errors());
        }
        die("sqlsrv_rows_returned $row_count instead of 1");
    }
    echo "rows = $row_count<br/>\n";

    dropTable($conn, $tableName);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

?>
--EXPECTF--
rows = 1<br/>
rows = 1<br/>
rows = 1<br/>
rows = 1<br/>
rows = 4<br/>
