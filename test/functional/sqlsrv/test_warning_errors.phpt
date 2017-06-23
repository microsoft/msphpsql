--TEST--
warnings as errors
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure( 'WarningsReturnAsErrors', true );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
 
require( 'MsCommon.inc' );

$conn = Connect();
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

// Should print connection warnings and not die
// Outputting the first two elements works around a bug in unixODBC that
// duplicates error messages and would otherwise cause the test to fail because
// of the extra output
echo "Warnings from sqlsrv_connect:\n";
print_r( sqlsrv_errors(SQLSRV_ERR_WARNINGS)[0] );
print_r( sqlsrv_errors(SQLSRV_ERR_WARNINGS)[1] ); 

echo "Errors from sqlsrv_connect:\n";
print_r( sqlsrv_errors(SQLSRV_ERR_ERRORS) );
$v1 = 1;
$v2 = 2;
$v3 = 1;  

// output parameters generate warnings
$stmt = sqlsrv_query( $conn, "raiserror('This is an error', 10, 1);");
if( $stmt === false ) {
    echo "Errors from raiserror\n";
    print_r( sqlsrv_errors() );
}

$stmt = sqlsrv_query( $conn, "{call test_out( ?, ?, ? )}", array( $v1, $v2, array( &$v3, SQLSRV_PARAM_OUT )));
if( $stmt === false ) {
    echo "Errors from sqlsrv_query with WarningsReturnAsErrors = true:\n";
    print_r( sqlsrv_errors(SQLSRV_ERR_ERRORS) );     // should be 1 warning of '3'
}

echo "Warnings from sqlsrv_query with WarningsReturnAsErrors = true:\n";
print_r( sqlsrv_errors(SQLSRV_ERR_WARNINGS) );   // should be nothing

echo "Output:\n$v3\n";

sqlsrv_configure( 'WarningsReturnAsErrors', false );
$stmt = sqlsrv_query( $conn, "{call test_out( ?, ?, ? )}", array( $v1, $v2, array( &$v3, SQLSRV_PARAM_OUT )));
if( $stmt === false ) {
    echo "Errors from sqlsrv_query with WarningsReturnAsErrors = false:\n";
    die( print_r( sqlsrv_errors() ));
}

echo "Warnings from sqlsrv_query with WarningsReturnAsErrors = false:\n";
print_r( sqlsrv_errors() );

echo "Output:\n$v3\n";

sqlsrv_close( $conn );

print "Test successful";
?> 
 
--EXPECTF--
Warnings from sqlsrv_connect:
Array
(
    [0] => 01000
    [SQLSTATE] => 01000
    [1] => 5701
    [code] => 5701
    [2] => %SChanged database context to '%S'.
    [message] => %SChanged database context to '%S'.
)
Array
(
    [0] => 01000
    [SQLSTATE] => 01000
    [1] => 5703
    [code] => 5703
    [2] => %SChanged language setting to us_english.
    [message] => %SChanged language setting to us_english.
)
Errors from sqlsrv_connect:
Errors from raiserror
Array
(
    [0] => Array
        (
            [0] => 01000
            [SQLSTATE] => 01000
            [1] => 50000
            [code] => 50000
            [2] => %SThis is an error
            [message] => %SThis is an error
        )

)
Errors from sqlsrv_query with WarningsReturnAsErrors = true:
Array
(
    [0] => Array
        (
            [0] => 01000
            [SQLSTATE] => 01000
            [1] => 0
            [code] => 0
            [2] => %S3
            [message] => %S3
        )

)
Warnings from sqlsrv_query with WarningsReturnAsErrors = true:
Output:
3
Warnings from sqlsrv_query with WarningsReturnAsErrors = false:
Array
(
    [0] => Array
        (
            [0] => 01000
            [SQLSTATE] => 01000
            [1] => 0
            [code] => 0
            [2] => %S3
            [message] => %S3
        )

)
Output:
3
Test successful 
