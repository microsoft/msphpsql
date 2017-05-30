--TEST--
warnings as errors
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

$stmt = sqlsrv_prepare( $conn, "SELECT * FROM [cd_info]");

$result = sqlsrv_field_metadata( $stmt );
if( $result === false ) {
    die( "sqlsrv_field_metadata should have succeeded." );
}

$result = sqlsrv_fetch( $stmt );
if( $result !== false ) {
    die( "sqlsrv_fetch should have failed because it wasn't yet executed." );
}
print_r( sqlsrv_errors() );

$result = sqlsrv_fetch_array( $stmt );
if( $result !== false ) {
    die( "sqlsrv_fetch_array should have failed because it wasn't yet executed." );
}
print_r( sqlsrv_errors() );

$result = sqlsrv_get_field( $stmt, 0 );
if( $result !== false ) {
    die( "sqlsrv_get_field should have failed because it wasn't yet executed." );
}
print_r( sqlsrv_errors() );

$result = sqlsrv_next_result( $stmt );
if( $result !== false ) {
    die( "sqlsrv_next_result should have failed because it wasn't yet executed." );
}
print_r( sqlsrv_errors() );

$result = sqlsrv_rows_affected( $stmt );
if( $result !== false ) {
    die( "sqlsrv_rows_affected should have failed because it wasn't yet executed." );
}
// Outputting the zero element of the error array works around a bug in the
// ODBC driver for Linux that produces an error message saying 'Cancel treated
// as FreeStmt/Close' on a statement that has not been executed.
print_r( sqlsrv_errors()[0] );

sqlsrv_execute( $stmt );

$result = sqlsrv_field_metadata( $stmt );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
print_r( sqlsrv_errors() );

$result = sqlsrv_rows_affected( $stmt );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$result = sqlsrv_fetch_array( $stmt );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$result = sqlsrv_fetch( $stmt );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$result = sqlsrv_get_field( $stmt, 0 );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$result = sqlsrv_next_result( $stmt );
if( $result === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

sqlsrv_free_stmt( $stmt );

sqlsrv_close( $conn );

print "Test successful";
?> 
--EXPECT--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -11
            [code] => -11
            [2] => The statement must be executed before results can be retrieved.
            [message] => The statement must be executed before results can be retrieved.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -11
            [code] => -11
            [2] => The statement must be executed before results can be retrieved.
            [message] => The statement must be executed before results can be retrieved.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -11
            [code] => -11
            [2] => The statement must be executed before results can be retrieved.
            [message] => The statement must be executed before results can be retrieved.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -11
            [code] => -11
            [2] => The statement must be executed before results can be retrieved.
            [message] => The statement must be executed before results can be retrieved.
        )

)
Array
(
    [0] => IMSSP
    [SQLSTATE] => IMSSP
    [1] => -11
    [code] => -11
    [2] => The statement must be executed before results can be retrieved.
    [message] => The statement must be executed before results can be retrieved.
)
Test successful
