--TEST--
scrollable results with no rows.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
    
    require( 'MsCommon.inc' );

    $conn = Connect();

    if( $conn === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }

    $stmt = sqlsrv_query( $conn, "IF OBJECT_ID('ScrollTest', 'U') IS NOT NULL DROP TABLE ScrollTest" );
    if( $stmt !== false ) { sqlsrv_free_stmt( $stmt ); }

    $stmt = sqlsrv_query( $conn, "CREATE TABLE ScrollTest (id int, value char(10))" );
    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    sqlsrv_free_stmt( $stmt );

    $stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest", array(), array( "Scrollable" => 'static' ));
    $rows = sqlsrv_has_rows( $stmt );
    if( $rows != false ) {
        FatalError( "Should be no rows present" );
    };

    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    $row = sqlsrv_fetch_array( $stmt );
    print_r( $row );
    if( $row === false ) {
        print_r( sqlsrv_errors(), true );
    }

    $stmt = sqlsrv_query( $conn, "SELECT * FROM ScrollTest" );
    $rows = sqlsrv_has_rows( $stmt );
    if( $rows != false ) {
        FatalError( "Should be no rows present" );
    };

    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    $row = sqlsrv_fetch_array( $stmt );
    print_r( $row );
    if( $row === false ) {
        print_r( sqlsrv_errors(), true );
    }

    $stmt = sqlsrv_query( $conn, "DROP TABLE ScrollTest" );
    
    echo "Test succeeded.\n";

?>
--EXPECT--
Test succeeded.
