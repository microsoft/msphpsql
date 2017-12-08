--TEST--
binding parameters, including output parameters, using the simplified syntax.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = AE\connect();

    $v1 = 1;
    $v2 = 2;
    $v3 = -1;

    // this is a test to infer the PHP type to be integer and check it so that it matches
    $stmt = sqlsrv_prepare($conn, "{call test_out( ?, ?, ? )}", array( &$v1, &$v2, array( &$v3, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )));

    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        print_r(sqlsrv_errors());
    }
    while (sqlsrv_next_result($stmt) != null);
    echo "$v3\n";

    sqlsrv_free_stmt($stmt);

    // this is a test to see if we force the type to be integer when it's inferred and the variable is a string
    $v3 = null;
    $stmt = sqlsrv_prepare($conn, "{call test_out( ?, ?, ? )}", array( &$v1, &$v2, array( &$v3, SQLSRV_PARAM_OUT, null, SQLSRV_SQLTYPE_INT )));

    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        print_r(sqlsrv_errors());
    }
    while (sqlsrv_next_result($stmt) != null);
    echo "$v3\n";

    sqlsrv_free_stmt($stmt);

    // For output parameters, if neither the php_type nor the sql_type is specified than the variable type is used to determine the php_type.
    // php type or sql type is specified, than it is an error case.
    $v3 = 3;
    $stmt = sqlsrv_prepare($conn, "{call test_out( ?, ?, ? )}", array( &$v1, &$v2, array( &$v3, SQLSRV_PARAM_OUT, null, null)));

    $result = sqlsrv_execute($stmt);
    if ($result === false) {
        print_r(sqlsrv_errors());
    }

    while (sqlsrv_next_result($stmt) != null);
    echo "$v3\n";

    sqlsrv_free_stmt($stmt);

    sqlsrv_close($conn);
?>
--EXPECT--
3
3
3
