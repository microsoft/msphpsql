--TEST--
sqlsrv_stmt_rows_affected.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
    sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_OFF );

    require( 'MsCommon.inc' );

    $conn = Connect();
    if( !$conn ) {
        FatalError( "Failed to connect." );
    }

    $stmt = sqlsrv_prepare( $conn, "IF OBJECT_ID('test_params', 'U') IS NOT NULL DROP TABLE test_params" );
    $result = sqlsrv_execute( $stmt );
    if( !$result ) {
        $errors = sqlsrv_errors();
        if( $errors[0]["SQLSTATE"] != "42S02" ) {
            var_dump( $errors );
            die( "sqlsrv_execute(2) failed." );
        }
    }
    sqlsrv_free_stmt( $stmt );
    
    $stmt = sqlsrv_prepare( $conn, "CREATE TABLE test_params (id tinyint, name char(10), [double] float, stuff varchar(4000))" );
    $result = sqlsrv_execute( $stmt );
    if( !$result ) {
        FatalError( "sqlsrv_execute(3) failed." );
    }
    sqlsrv_free_stmt( $stmt );

    $f1 = 1;
    $f2 = "testtestte";
    $f3 = 12.0;
    $f4 = fopen( "data://text/plain,This%20is%20some%20text%20meant%20to%20test%20binding%20parameters%20to%20streams", "r" );
    $stmt = sqlsrv_prepare( $conn, "INSERT INTO test_params (id, name, [double], stuff) VALUES (?, ?, ?, ?)", array( &$f1, &$f2, &$f3, &$f4 )); 
    if( !$stmt ) {
        FatalError( "sqlsrv_prepare(4) failed." );        
    }
    
    for( $record = 1; $record <= 4; ++$record ) {
        $success = sqlsrv_execute( $stmt );
        if( !$success ) {
            FatalError( "sqlsrv_execute($record) failed." );        
        }
        while( $success = sqlsrv_send_stream_data( $stmt )) {
        }
        if( !is_null( $success )) {
            sqlsrv_cancel( $stmt );
            sqlsrv_free_stmt( $stmt );
            die( "sqlsrv_send_stream_data failed." );
        }
        $row_count = sqlsrv_rows_affected( $stmt );
        if( $row_count != 1 ) {
            if( $row_count == -1 ) {
                var_dump( sqlsrv_errors() );
            }
            die( "sqlsrv_rows_returned $row_count instead of 1" );
        }
        echo "rows = $row_count<br/>\n";
    }
    sqlsrv_free_stmt( $stmt );

    $stmt = sqlsrv_prepare( $conn, "UPDATE test_params SET [double] = 13.0 FROM test_params WHERE [double] = 12.0" );
    if( !$stmt ) {
        FatalError( "sqlsrv_prepare(2) failed." );        
    }
    $success = sqlsrv_execute( $stmt );
    if( !$success ) {
        FatalError( "sqlsrv_execute(5) failed." );        
    }
    $row_count = sqlsrv_rows_affected( $stmt );
    if( $row_count != 4 ) {
        if( $row_count == -1 ) {
            var_dump( sqlsrv_errors() );
        }
        die( "sqlsrv_rows_returned $row_count instead of 1" );
    }
    echo "rows = $row_count<br/>\n";

    sqlsrv_query( $conn, "DROP TABLE test_params" );

    sqlsrv_free_stmt( $stmt );
    sqlsrv_close( $conn );

?>
--EXPECTF--
rows = 1<br/>
rows = 1<br/>
rows = 1<br/>
rows = 1<br/>
rows = 4<br/>

