--TEST--
test new error messages when sqlsrv_get_field is called 
--DESCRIPTION--
new error messages when sqlsrv_get_field is called before sqlsrv_fetch or 
if sqlsrv_get_field is called with out of order field indices.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

require( 'MsCommon.inc' );

$conn = Connect(); 
if (!$conn)
{
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query( $conn, "SELECT * FROM test_types" );
if (!$stmt)
{
    die( print_r( sqlsrv_errors(), true ));
}

$result = sqlsrv_get_field( $stmt, 0 );
if( $result !== false ) {
    die( "sqlsrv_get_field before sqlsrv_fetch shouldn't have succeeded." );
}
print_r( sqlsrv_errors() );

$result = sqlsrv_fetch( $stmt );
if ($result === false)
{
    die( print_r( sqlsrv_errors(), true ));
}

$result = sqlsrv_get_field( $stmt, 3 );
$result = sqlsrv_get_field( $stmt, 0 );
if( $result !== false ) {
    die( "sqlsrv_get_field for field 0 shouldn't have succeeded." );
}
print_r( sqlsrv_errors() );

sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );

?>
--EXPECT--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -18
            [code] => -18
            [2] => A row must be retrieved with sqlsrv_fetch before retrieving data with sqlsrv_get_field.
            [message] => A row must be retrieved with sqlsrv_fetch before retrieving data with sqlsrv_get_field.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -19
            [code] => -19
            [2] => Fields within a row must be accessed in ascending order. The sqlsrv_get_field function cannot retrieve field 0 because its index is less than the index of a field that has already been retrieved (3).
            [message] => Fields within a row must be accessed in ascending order. The sqlsrv_get_field function cannot retrieve field 0 because its index is less than the index of a field that has already been retrieved (3).
        )

)
