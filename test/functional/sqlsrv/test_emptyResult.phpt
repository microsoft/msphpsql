--TEST--
Empty result set from query should not return an error
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

require( 'MsCommon.inc' );

$conn = Connect(); 

$stmt = sqlsrv_query( $conn, "IF OBJECT_ID('EmptyTable', 'U') IS NOT NULL DROP TABLE EmptyTable" );
if( $stmt !== false ) sqlsrv_free_stmt( $stmt );

$stmt = sqlsrv_query( $conn, "CREATE TABLE EmptyTable (id int, value char(10))" );
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query($conn, "DELETE FROM EmptyTable");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$rows = sqlsrv_rows_affected( $stmt );
echo "rows deleted = $rows\n";
sqlsrv_free_stmt( $stmt );

$stream = fopen( "data://text/plain,", "r" );

$stmt = sqlsrv_query($conn, "DELETE FROM EmptyTable WHERE value = ?", array( $stream ));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$rows = sqlsrv_rows_affected( $stmt );
echo "rows deleted = $rows\n";
sqlsrv_free_stmt( $stmt );

sqlsrv_query( $conn, "DROP TABLE EmptyTable" );

sqlsrv_close( $conn );

echo "Test succeeded.\n";

?>
--EXPECT--
rows deleted = 0
rows deleted = 0
Test succeeded.
