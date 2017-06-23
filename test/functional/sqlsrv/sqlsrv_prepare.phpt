--TEST--
binding parameters, including output parameters, using the simplified syntax.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require( 'MsCommon.inc' );

    $conn = Connect();
    if ( !$conn )
    {
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

    $v1 = 1;
    $v2 = 2;
    $v3 = -1;  // must initialize output parameters to something similar to what they are projected to receive

    $stmt = sqlsrv_prepare( $conn, "{call test_out( ?, ?, ? )}", array( &$v1, &$v2, array( &$v3, SQLSRV_PARAM_OUT )));
    
    // Turning off WarningsReturnAsErrors, because of the print at the end of test_out proc, 
    // which causes a warning. Warning contains the result of print.
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    $ret = sqlsrv_execute( $stmt );
    if ( $ret === false )
    {
        print_r( sqlsrv_errors());
    }
    sqlsrv_configure( 'WarningsReturnAsErrors', 1 );
    while( sqlsrv_next_result( $stmt ) != null );    
    // this should return 3, but shorthand output parameters are disabled for now.
    echo "$v3\n";
    
    $v1 = 2;
    
    // Turning off WarningsReturnAsErrors, because of the print at the end of test_out proc, 
    // which causes a warning. Warning contains the result of print.
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    $ret = sqlsrv_execute( $stmt );
    if ( $ret === false )
    {
        print_r( sqlsrv_errors());
    }
    sqlsrv_configure( 'WarningsReturnAsErrors', 1 );
    while( sqlsrv_next_result( $stmt ) != null );
    
    // this should return 4, but shorthand output parameters are disabled for now.
    echo "$v3\n";
    
    sqlsrv_query( $conn, "DROP TABLE test_params" );

    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );
?>
--EXPECTF--
1
12.0
testtestte
This is some text meant to test binding parameters to streams
2
13.0
testtestte
This is some more text meant to test binding parameters to streams
3
4
