--TEST--
sqlsrv_num_fields and output params without sqlsrv_next_result.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = AE\connect();

    // test num_fields on a statement that doesn't generate a result set.
    $stmt = sqlsrv_prepare($conn, "USE 'tempdb'");
    sqlsrv_execute($stmt);
    $field_count = sqlsrv_num_fields($stmt);
    if ($field_count === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    echo "$field_count\n";
    sqlsrv_free_stmt($stmt);

    // test sqlsrv_num_fields immediately after creating a table
    $tableName = 'test_params';
    $columns = array(new AE\ColumnMeta('tinyint', 'id'),
                     new AE\ColumnMeta('char(10)', 'name'),
                     new AE\ColumnMeta('float', 'double'),
                     new AE\ColumnMeta('varchar(max)', 'stuff'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    
    $field_count = sqlsrv_num_fields($stmt);
    if ($field_count === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    echo "$field_count\n";
    sqlsrv_execute($stmt);
    sqlsrv_free_stmt($stmt);

    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");

    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, "testtestte", &$f3, &$f4 ));
    if (!$stmt) {
        fatalError("sqlsrv_prepare failed.");
    }

    $success = sqlsrv_execute($stmt);
    if (!$success) {
        fatalError("sqlsrv_execute failed.");
    }
    while ($success = sqlsrv_send_stream_data($stmt)) {
    }
    if (!is_null($success)) {
        sqlsrv_cancel($stmt);
        sqlsrv_free_stmt($stmt);
        die("sqlsrv_send_stream_data failed.");
    }

    sqlsrv_free_stmt($stmt);

    // test num_fields on a valid statement that produces a result set.
    $stmt = sqlsrv_prepare($conn, "SELECT id, [double], name, stuff FROM $tableName");
    $success = sqlsrv_execute($stmt);
    if (!$success) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_execute failed.");
    }
    $success = sqlsrv_fetch($stmt);
    if (!$success) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_execute failed.");
    }
    $field_count = sqlsrv_num_fields($stmt);
    if ($field_count === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    echo "$field_count\n";

    $v1 = 1;
    $v2 = 2;
    $v3 = -1;  // must initialize output parameters to something similar to what they are projected to receive

    $stmt = sqlsrv_prepare($conn, "{call test_out( ?, ?, ? )}", array( &$v1, &$v2, array( &$v3, SQLSRV_PARAM_OUT )));

    sqlsrv_execute($stmt);
    // while( sqlsrv_next_result( $stmt ) != null );
    // this should return 3, but shorthand output parameters are disabled for now.
    echo "$v3\n";

    dropTable($conn, $tableName);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
?>
--EXPECT--
0
0
4
3
