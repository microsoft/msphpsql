--TEST--
large types to strings of 1MB size.
--DESCRIPTION--
This includes a test by providing an invalid php type.
--SKIPIF--
<?php require('skipif_azure_dw.inc'); ?>
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

    $stmt = sqlsrv_query( $conn, "SELECT * FROM [test_streamable_types]" );
    if( $stmt == false ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_query failed." );
    }
   
    sqlsrv_fetch( $stmt );
    $str = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY) );
    if( $str === false || strlen( $str ) != 1024*1024 ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field(1) failed." );
    }
    
    $str = sqlsrv_get_field( $stmt, 1, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY) );
    if( $str === false || strlen( $str ) != 1024*1024*2 ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field(2) failed." );
    }

    $str = sqlsrv_get_field( $stmt, 2, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY) );
    if( $str === false || strlen( $str ) != 1024*1024 ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field(3) failed." );
    }

    $str = sqlsrv_get_field( $stmt, 3, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY) );
    if( $str === false || strlen( $str ) != 1024*1024 ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field(4) failed." );
    }

    $str = sqlsrv_get_field( $stmt, 4, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY) );
    if( $str === false || strlen( $str ) != 1024*1024*2 ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field(5) failed." );
    }

    $str = sqlsrv_get_field( $stmt, 5, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY) );
    if( $str === false || strlen( $str ) != 1024*1024 ) {
        var_dump( sqlsrv_errors() );
        die( "sqlsrv_get_field(6) failed." );
    }

    $str = sqlsrv_get_field( $stmt, 0, SQLSRV_PHPTYPE_STRING("UTF") );
    if ($str === false) {
        $error = sqlsrv_errors()[0]['message'];
        if ($error !== 'Invalid type') {
            fatalError('Unexpected error returned');
        }
    } else {
        echo "Expect sqlsrv_get_field(7) to fail!\n";
    }

    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );

    echo "Test successful.\n";
?>
--EXPECT--
Test successful.
