--TEST--
binding parameters, including output parameters, using the simplified syntax.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
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

    sqlsrv_free_stmt($stmt);

    $stmt = sqlsrv_prepare($conn, "SELECT id, [double], name, stuff FROM $tableName");
    $success = sqlsrv_execute($stmt);
    if (!$success) {
        var_dump(sqlsrv_errors());
        die("sqlsrv_execute failed.");
    }

    $textValues = array("This is some text meant to test binding parameters to streams", 
                        "This is some more text meant to test binding parameters to streams");
    $k = 0;
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
                if ($str !== $textValues[$k++]) {
                    fatalError("Incorrect data: \'$str\'!\n");
                }
            }
        }
        echo "\n";
    }

    $v1 = 1;
    $v2 = 2;
    $v3 = -1;  // must initialize output parameters to something similar to what they are projected to receive

    $stmt = sqlsrv_prepare($conn, "{call test_out( ?, ?, ? )}", array( &$v1, &$v2, array( &$v3, SQLSRV_PARAM_OUT )));

    // Turning off WarningsReturnAsErrors, because of the print at the end of test_out proc,
    // which causes a warning. Warning contains the result of print.
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    $ret = sqlsrv_execute($stmt);
    if ($ret === false) {
        print_r(sqlsrv_errors());
    }
    sqlsrv_configure('WarningsReturnAsErrors', 1);
    while (sqlsrv_next_result($stmt) != null);
    // this should return 3, but shorthand output parameters are disabled for now.
    echo "$v3\n";

    $v1 = 2;

    // Turning off WarningsReturnAsErrors, because of the print at the end of test_out proc,
    // which causes a warning. Warning contains the result of print.
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    $ret = sqlsrv_execute($stmt);
    if ($ret === false) {
        print_r(sqlsrv_errors());
    }
    sqlsrv_configure('WarningsReturnAsErrors', 1);
    while (sqlsrv_next_result($stmt) != null);

    // this should return 4, but shorthand output parameters are disabled for now.
    echo "$v3\n";

    sqlsrv_query($conn, "DROP TABLE $tableName");

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
?>
--EXPECT--
1
12.0
testtestte

2
13.0
testtestte

3
4
