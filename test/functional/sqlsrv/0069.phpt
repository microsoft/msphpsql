--TEST--
Variety of connection parameters.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    set_time_limit(0); 
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    date_default_timezone_set( 'America/Vancouver' );

    require( 'MsCommon.inc' );
    $conn = Connect();

    if( $conn === false ) {
        die( print_r( sqlsrv_errors(), true ));
    } 
    $stmt = sqlsrv_query($conn, "IF OBJECT_ID('php_table_SERIL1_1', 'U') IS NOT NULL DROP TABLE [php_table_SERIL1_1]");
    if( $stmt !== false ) sqlsrv_free_stmt( $stmt );
     
    $stmt = sqlsrv_query($conn, "CREATE TABLE [php_table_SERIL1_1] ([c1_datetime2] datetime2(0), [c2_datetimeoffset] datetimeoffset(0), [c3_time] time(0))");
    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    sqlsrv_free_stmt($stmt);

    // test inserting into date time as a default
    $datetime2 = date_create( '1963-02-01 20:56:04.0123456' );
    $datetimeoffset = date_create( '1963-02-01 20:56:04.0123456 -07:00' );
    $time = date_create( '20:56:04.98765' );

    $stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c1_datetime2, c2_datetimeoffset, c3_time) VALUES (?,?,?)", array( $datetime2, $datetimeoffset, $time ));
    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    print_r( sqlsrv_errors( SQLSRV_ERR_WARNINGS ));

    $stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c1_datetime2, c2_datetimeoffset, c3_time) VALUES (?,?,?)", 
          array( 
              array( $datetime2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATETIME2 ),
              array( $datetimeoffset, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATETIMEOFFSET ),
              array( $time, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_TIME )));
    if( $stmt === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    print_r( sqlsrv_errors( SQLSRV_ERR_WARNINGS ));

    $stmt = sqlsrv_query( $conn, "DROP TABLE [php_table_SERIL1_1]" );

    sqlsrv_close($conn); 

    echo "test succeeded.";

?>
--EXPECT--
test succeeded.
