--TEST--
test input param with unknown encoding
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

set_time_limit(0);
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_OFF );

require( 'MsCommon.inc' );

$conn = Connect();
if( $conn === false ) {
    die( print_r( sqlsrv_errors(), true ));
}

$stmt = sqlsrv_query($conn, "IF OBJECT_ID('php_table_SERIL1_1', 'U') IS NOT NULL DROP TABLE [php_table_SERIL1_1]");
if( $stmt !== false ) {
    sqlsrv_free_stmt( $stmt );
}

$stmt = sqlsrv_query($conn, "CREATE TABLE [php_table_SERIL1_1] ([c1_int] int, [c2_varchar_max] varchar(max))");
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UNKNOWN), null)));
if( $stmt !== false ) {
    sqlsrv_free_stmt( $stmt );
    die( "sqlsrv_query shouldn't have succeeded." );
}
print_r( sqlsrv_errors() );

$stmt = sqlsrv_prepare($conn, "INSERT INTO [php_table_SERIL1_1] (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UNKNOWN), null)));
if( $stmt === false ) {
    die( print_r( sqlsrv_errors(), true ));
}
$result = sqlsrv_execute( $stmt );
if( $result !== false ) {
    sqlsrv_free_stmt( $stmt );
    die( "sqlsrv_execute shouldn't have succeeded." );
}
print_r( sqlsrv_errors() );

sqlsrv_query($conn, "DROP TABLE [php_table_SERIL1_1]");

sqlsrv_close($conn);
 
?>
--EXPECTREGEX--
(Warning|Notice)\: Use of undefined constant SQLSRV_ENC_UNKNOWN - assumed \'SQLSRV_ENC_UNKNOWN\' (\(this will throw an Error in a future version of PHP\) )?in .+(\/|\\)sqlsrv_input_param_unknown_encoding\.php on line 26
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -16
            \[code\] => -16
            \[2\] => An invalid PHP type for parameter 2 was specified\.
            \[message\] => An invalid PHP type for parameter 2 was specified\.
        \)

\)

(Warning|Notice)\: Use of undefined constant SQLSRV_ENC_UNKNOWN - assumed \'SQLSRV_ENC_UNKNOWN\' (\(this will throw an Error in a future version of PHP\) )?in .+(\/|\\)sqlsrv_input_param_unknown_encoding\.php on line 33
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => -16
            \[code\] => -16
            \[2\] => An invalid PHP type for parameter 2 was specified\.
            \[message\] => An invalid PHP type for parameter 2 was specified\.
        \)

\)
