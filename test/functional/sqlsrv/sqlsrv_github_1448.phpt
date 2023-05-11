--TEST--
Test for Github Issue 1448
--DESCRIPTION--
Prepare and execute with int, then execute with string caused "Invalid character value for cast specification" error.
Repro script provided by thsmrtone1
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require_once( 'MsCommon.inc' );
    $conn = Connect();
    if( !$conn ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_connect failed." );
    }

    sqlsrv_query($conn, "CREATE TABLE test1448 (testCol nvarchar(50) NULL)");

    $v0 = 1000;
    $stmt = sqlsrv_prepare($conn, 'INSERT INTO [test1448] (testCol) VALUES (?);', [&$v0]);
    sqlsrv_execute($stmt);

    $v0 = 'abcd';
    sqlsrv_execute($stmt);

    $error = sqlsrv_errors(SQLSRV_ERR_ERRORS);
    var_dump($error);

    dropTable($conn, "test1448");
    sqlsrv_close($conn);
?>
--EXPECT--
NULL
