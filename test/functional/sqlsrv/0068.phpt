--TEST--
warnings for non reference variables.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    sqlsrv_configure( 'WarningsReturnAsErrors', false );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );
    $conn = Connect();
    if( !$conn ) {
        FatalError( "sqlsrv_connect failed." );
    }

    $stmt = sqlsrv_query( $conn, "IF OBJECT_ID('test_empty_stream', 'U') IS NOT NULL DROP TABLE test_empty_stream" );
    if( $stmt !== false ) sqlsrv_free_stmt( $stmt );

    $stmt = sqlsrv_query( $conn, "CREATE TABLE test_empty_stream (id int, varchar_stream varchar(max), varbinary_stream varbinary(max))");
    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }

    $f1 = 1;
    $f2 = fopen( "data://text/plain,", "r" );
    $stmt = sqlsrv_prepare( $conn, "INSERT INTO test_empty_stream (id, varchar_stream, varbinary_stream) VALUES (?, ?, ?)", array( $f1, array( $f2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM('binary'), SQLSRV_SQLTYPE_VARCHAR('max') ),
          fopen( "data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" )));
    if( $stmt === false ) {
        print_r( "sqlsrv_prepare failed." );
        print_r( sqlsrv_errors() );
    }
    $result = sqlsrv_execute($stmt);
    if( $result === false ) {
         print_r( "sqlsrv_execute(3) failed\n" );
         print_r( sqlsrv_errors() );
    }
    
    $stmt = sqlsrv_query( $conn, "DROP TABLE test_empty_stream" );

    sqlsrv_close( $conn );
?>
--EXPECTREGEX--
sqlsrv_execute\(3\) failed
Array
\(
    \[0\] => Array
        \(
            \[0\] => 42000
            \[SQLSTATE\] => 42000
            \[1\] => 257
            \[code\] => 257
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Implicit conversion from data type varchar\(max\) to varbinary\(max\) is not allowed\. Use the CONVERT function to run this query\.
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Implicit conversion from data type varchar\(max\) to varbinary\(max\) is not allowed\. Use the CONVERT function to run this query\.
        \)

    \[1\] => Array
        \(
            \[0\] => 42000
            \[SQLSTATE\] => 42000
            \[1\] => 8180
            \[code\] => 8180
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Statement\(s\) could not be prepared\.
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Statement\(s\) could not be prepared\.
        \)

\)