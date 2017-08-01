--TEST--
datetime server neutral to make sure it passes.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

set_time_limit(0);
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_ALL );

require( 'MsCommon.inc' );
$conn = Connect();
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('test_datetime', 'U') IS NOT NULL DROP TABLE test_datetime" );
if( $stmt !== false ) sqlsrv_free_stmt( $stmt );
    
$stmt = sqlsrv_query( $conn, "CREATE TABLE test_datetime (id int, c2_datetime datetime, c3_smalldatetime smalldatetime)" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_query( $conn, "INSERT INTO test_datetime (id, c2_datetime, c3_smalldatetime) VALUES (?, ?, ?)", 
                      array(array(1912963494, null, null, SQLSRV_SQLTYPE_INT), 
                            array('5413-07-02 02:24:18.791', null, null, SQLSRV_SQLTYPE_DATETIME), 
                            array('1927-07-29 08:37:00', null, null, SQLSRV_SQLTYPE_SMALLDATETIME)));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

sqlsrv_free_stmt( $stmt );

$server_info = sqlsrv_server_info( $conn );
print_r( $server_info[ 'SQLServerVersion' ] );

sqlsrv_query( $conn, "DROP TABLE test_datetime" );

sqlsrv_close( $conn );  

?>
--EXPECTREGEX--
1[0-9]\.[0-9]{2}\.[0-9]{4}
