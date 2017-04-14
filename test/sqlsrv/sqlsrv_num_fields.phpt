--TEST--
sqlsrv_num_fields and output params without sqlsrv_next_result.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

require_once( "autonomous_setup.php" );

$conn = sqlsrv_connect( $serverName, $connectionInfo );
if( !$conn ) {
	var_dump( sqlsrv_errors() );
	die( "sqlsrv_create failed." );
}

// test num_fields on a statement that doesn't generate a result set.
$stmt = sqlsrv_prepare( $conn, "DECLARE @var INT;" );
sqlsrv_execute( $stmt );
$field_count = sqlsrv_num_fields( $stmt );
if( $field_count === false ) {
	die( print_r( sqlsrv_errors(), true ));
}
echo "$field_count\n";
sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_prepare( $conn, "IF OBJECT_ID('test_params', 'U') IS NOT NULL DROP TABLE test_params" );
sqlsrv_execute( $stmt );
sqlsrv_free_stmt( $stmt );

// test sqlsrv_num_fields immediately after a prepare
$stmt = sqlsrv_prepare( $conn, "CREATE TABLE test_params (id tinyint, name char(10), [double] float, stuff varchar(max))" );
$field_count = sqlsrv_num_fields( $stmt );
if( $field_count === false ) {
	die( print_r( sqlsrv_errors(), true ));
}
echo "$field_count\n";
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

sqlsrv_free_stmt( $stmt );

// test num_fields on a valid statement that produces a result set.
$stmt = sqlsrv_prepare( $conn, "SELECT id, [double], name, stuff FROM test_params" );
$success = sqlsrv_execute( $stmt );
if( !$success ) {
	var_dump( sqlsrv_errors() );
	die( "sqlsrv_execute failed." );        
}
$success = sqlsrv_fetch( $stmt );
if( !$success ) {
	var_dump( sqlsrv_errors() );
	die( "sqlsrv_execute failed." );        
}
$field_count = sqlsrv_num_fields( $stmt );
if( $field_count === false ) {
	die( print_r( sqlsrv_errors(), true ));
}
echo "$field_count\n";

$v1 = 1;
$v2 = 2;
$v3 = -1;  // must initialize output parameters to something similar to what they are projected to receive

$stmt = sqlsrv_prepare( $conn, "{call test_out( ?, ?, ? )}", array( &$v1, &$v2, array( &$v3, SQLSRV_PARAM_OUT )));

sqlsrv_execute( $stmt );
echo "$v3\n";
sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_prepare( $conn, "IF OBJECT_ID('test_params', 'U') IS NOT NULL DROP TABLE test_params" );
sqlsrv_execute( $stmt );
sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );
?>
--EXPECT--
0
0
4
3
