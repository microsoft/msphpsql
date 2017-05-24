--TEST--
binding streams using full syntax.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );
    
    $conn = Connect();
    if( $conn === false ) {
        FatalError( "Failed to connect." );
    }

    $stmt = sqlsrv_query( $conn, "IF OBJECT_ID('test_params', 'U') IS NOT NULL DROP TABLE test_params" );
    if( $stmt !== false ) {
        sqlsrv_free_stmt( $stmt );
    }

    $stmt = sqlsrv_query( $conn, "CREATE TABLE test_params (id tinyint, name char(10), [double] float, stuff varbinary(max))" );
    sqlsrv_free_stmt( $stmt );

    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen( "data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );

    // use full details
    $stmt = sqlsrv_query( $conn, "INSERT INTO test_params (id, name, [double], stuff) VALUES (?, ?, ?, ?)",
                            array(
                                array( &$f1, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_INT, SQLSRV_SQLTYPE_TINYINT ),
                                array( &$f2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_CHAR(10)),
                                array( &$f3, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_FLOAT, SQLSRV_SQLTYPE_FLOAT),
                                array( &$f4, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY), SQLSRV_SQLTYPE_VARBINARY('max'))));
    if( $stmt === false ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_query(1) failed." );        
    }
    while( $success = sqlsrv_send_stream_data( $stmt )) {
    }
    if( !is_null( $success )) {
        print_r( sqlsrv_errors() );
        sqlsrv_cancel( $stmt );
        sqlsrv_free_stmt( $stmt );
        die( "sqlsrv_send_stream_data(1) failed." );
    }

    fclose( $f4 );
    $f4 = fopen( "data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
    // use nulls for php types
    $stmt = sqlsrv_query( $conn, "INSERT INTO test_params (id, name, [double], stuff) VALUES (?, ?, ?, ?)",
                            array(
                                array( &$f1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_TINYINT ),
                                array( &$f2, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_CHAR(10)),
                                array( &$f3, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_FLOAT),
                                array( &$f4, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARBINARY('max'))));
    if( $stmt !== false ) {
		die( "sqlsrv_query(2) should have failed." );
    }
    print_r( sqlsrv_errors() );
    print_r( "sqlsrv_query(2) failed.\n" );
    fclose( $f4 );

    // try it with nothing but default values
    $f4 = fopen( "data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
    // use nulls for php types
    $stmt = sqlsrv_query( $conn, "INSERT INTO test_params (id, name, [double], stuff) VALUES (?, ?, ?, ?)",
                            array(
                                array( &$f1, null, null, null ),
                                array( &$f2, null, null, null ),
                                array( &$f3, null, null, null ),
                                array( &$f4, null, null, null )));
    if( $stmt === false ) {
        print_r( sqlsrv_errors() );
        print_r( "sqlsrv_query(3) failed.\n" );
    }
    else {
        sqlsrv_free_stmt( $stmt );
        die( "sqlsrv_query(3) shouldn't have succeeded." );
    }

    // print out the results for comparison
    $stmt = sqlsrv_query( $conn, "SELECT id, [double], name, stuff FROM test_params" );
    if( $stmt === false ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_execute failed." );        
    }
    while( sqlsrv_fetch( $stmt )) {
        $id = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$id\n";
        $double = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$double\n";
        $name = sqlsrv_get_field( $stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR));
        echo "$name\n";
        $stream = sqlsrv_get_field( $stmt, 3, SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
        while( !feof( $stream )) { 
            $str = fread( $stream, 10000 );
            echo $str;
        }
        echo "\n";
    }
    sqlsrv_free_stmt( $stmt );
    fclose( $f4 );

    // try it with nothing but default values
    $f4 = fopen( "data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
    // use nulls for php types
    $stmt = sqlsrv_query( $conn, "INSERT INTO test_params (id, name, [double], stuff) VALUES (?, ?, ?, ?)",
                            array(
                                array( &$f1, null, null, null ),
                                array(),
                                array( &$f3, null, null, null ),
                                array( &$f4, null, null, null )));
    if( $stmt !== false ) {
        die( "sqlsrv_query should have failed." );        
    }
    var_dump( sqlsrv_errors() );
    fclose( $f4 );
    
    sqlsrv_query( $conn, "DROP TABLE test_params" );
    
    sqlsrv_close( $conn );
?>
--EXPECTF--
Array
(
    [0] => Array
        (
            [0] => 22018
            [SQLSTATE] => 22018
            [1] => 0
            [code] => 0
            [2] => %SInvalid character value for cast specification
            [message] => %SInvalid character value for cast specification
        )

)
sqlsrv_query(2) failed.
Array
(
    [0] => Array
        (
            [0] => 42000
            [SQLSTATE] => 42000
            [1] => 257
            [code] => 257
            [2] => %SImplicit conversion from data type varchar(max) to varbinary(max) is not allowed. Use the CONVERT function to run this query.
            [message] => %SImplicit conversion from data type varchar(max) to varbinary(max) is not allowed. Use the CONVERT function to run this query.
        )

)
sqlsrv_query(3) failed.
1
12.0
testtestte
This is some text meant to test binding parameters to streams
array(1) {
  [0]=>
  array(6) {
    [0]=>
    string(5) "IMSSP"
    ["SQLSTATE"]=>
    string(5) "IMSSP"
    [1]=>
    int(-9)
    ["code"]=>
    int(-9)
    [2]=>
    string(59) "Parameter array 2 must have at least one value or variable."
    ["message"]=>
    string(59) "Parameter array 2 must have at least one value or variable."
  }
}
