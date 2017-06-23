--TEST--
error messages when trying to retrieve past the end of a result set and when no result set exists.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );
    $conn = Connect();
    if( !$conn ) {
        FatalError( "Failed to connect." );
    }

    $stmt = sqlsrv_prepare( $conn, "IF OBJECT_ID('test_params', 'U') IS NOT NULL DROP TABLE test_params" );
    sqlsrv_execute( $stmt );
    sqlsrv_free_stmt( $stmt );
    
    $stmt = sqlsrv_prepare( $conn, "CREATE TABLE test_params (id tinyint, name char(10), [double] float, stuff varchar(max))" );
    sqlsrv_execute( $stmt );
    sqlsrv_free_stmt( $stmt );

    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen( "data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );

    $stmt = sqlsrv_prepare( $conn, "INSERT INTO test_params (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, "testtestte", &$f3, &$f4 ));
    if( !$stmt ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_prepare failed." );        
    }

    $success = sqlsrv_execute( $stmt );
    if( !$success ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_execute failed." );        
    }
    while( $success = sqlsrv_send_stream_data( $stmt )) {
    }
    if( !is_null( $success )) {
        sqlsrv_cancel( $stmt );
        sqlsrv_free_stmt( $stmt );
        die( "sqlsrv_send_stream_data failed." );
    }

    $result = sqlsrv_fetch( $stmt );
    if( $result !== false ) {
        die( "sqlsrv_fetch should have failed." );
    }
    print_r( sqlsrv_errors() );

    $f1 = 2;
    $f3 = 13.0;
    $f4 = fopen( "data://text/plain,This%20is%20some%20more%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
    $success = sqlsrv_execute( $stmt );
    if( !$success ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_execute failed." );        
    }
    while( $success = sqlsrv_send_stream_data( $stmt )) {
    }
    if( !is_null( $success )) {
        sqlsrv_cancel( $stmt );
        sqlsrv_free_stmt( $stmt );
        die( "sqlsrv_send_stream_data failed." );
    }

    sqlsrv_free_stmt( $stmt );

    $stmt = sqlsrv_prepare( $conn, "SELECT id, [double], name, stuff FROM test_params" );
    $success = sqlsrv_execute( $stmt );
    if( !$success ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_execute failed." );        
    }
    
    while( sqlsrv_fetch( $stmt )) {
        $id = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR) );
        echo "$id\n";
        $double = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR) );
        echo "$double\n";
        $name = sqlsrv_get_field( $stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR) );
        echo "$name\n";
        $stream = sqlsrv_get_field( $stmt, 3, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY) );
        while( !feof( $stream )) { 
            $str = fread( $stream, 10000 );
            echo $str;
        }
        echo "\n";
    }

    $result = sqlsrv_fetch( $stmt );
    if( $result !== false ) {
        die( "sqlsrv_fetch should have failed." );
    }
    print_r( sqlsrv_errors() );

    $result = sqlsrv_next_result( $stmt );
    if( $result === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    $result = sqlsrv_next_result( $stmt );
    if( $result !== false ) {
        die( "sqlsrv_next_result should have failed." );
    }
    print_r( sqlsrv_errors() );
    
    sqlsrv_query( $conn, "DROP TABLE test_params" );

    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );
?>
--EXPECT--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -28
            [code] => -28
            [2] => The active result for the query contains no fields.
            [message] => The active result for the query contains no fields.
        )

)
1
12.0
testtestte
This is some text meant to test binding parameters to streams
2
13.0
testtestte
This is some more text meant to test binding parameters to streams
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -22
            [code] => -22
            [2] => There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.
            [message] => There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -26
            [code] => -26
            [2] => There are no more results returned by the query.
            [message] => There are no more results returned by the query.
        )

)