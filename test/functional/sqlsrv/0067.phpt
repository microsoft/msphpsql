--TEST--
inserting UTF-8 text via a PHP and error conditions.
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
    
    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen("data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r");
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array(&$f1, "testtestte", &$f3, array( &$f4, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM('utf-8')))); //,
    if (!$stmt) {
        var_dump(sqlsrv_errors());
        fatalError("sqlsrv_prepare failed.");
    }

    $success = sqlsrv_execute($stmt);

    // test UTF-8 cutoff in the middle of a valid 3 byte UTF-8 char
    $utf8 = str_repeat("41", 8188);
    $utf8 = $utf8 . "e38395";
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");

    $success = sqlsrv_execute($stmt);
    if ($success === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // test a 2 byte incomplete character
    $utf8 = str_repeat("41", 8188);
    $utf8 = $utf8 . "dfa0";
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");

    $success = sqlsrv_execute($stmt);
    if ($success === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // test a 4 byte incomplete character
    $utf8 = str_repeat("41", 8186);
    $utf8 = $utf8 . "f1a680bf";
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");

    $success = sqlsrv_execute($stmt);
    if ($success === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    // test UTF-8 cutoff (really cutoff)
    $utf8 = str_repeat("41", 8188);
    $utf8 = $utf8 . "e383";
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");

    $success = sqlsrv_execute($stmt);
    if ($success !== false) {
        fatalError('Should have failed with a cutoff UTF-8 string');
    }
    print_r(sqlsrv_errors());

    // test UTF-8 invalid/corrupt stream
    $utf8 = str_repeat("41", 8188);
    $utf8 = $utf8 . "e38395e38395";
    $utf8 = substr_replace($utf8, "fe", 1000, 2);
    $utf8 = pack("H*", $utf8);
    $f4 = fopen("data://text/plain," . $utf8, "r");

    $success = sqlsrv_execute($stmt);
    if ($success !== false) {
        fatalError('Should have failed with an invalid UTF-8 string');
    }
    print_r(sqlsrv_errors());

    dropTable($conn, $tableName);

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo "Test succeeded\n";

?>
--EXPECTF--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -43
            [code] => -43
            [2] => An error occurred translating a PHP stream from UTF-8 to UTF-16: %a
            [message] => An error occurred translating a PHP stream from UTF-8 to UTF-16: %a
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -43
            [code] => -43
            [2] => An error occurred translating a PHP stream from UTF-8 to UTF-16: %a
            [message] => An error occurred translating a PHP stream from UTF-8 to UTF-16: %a
        )

)
Test succeeded
