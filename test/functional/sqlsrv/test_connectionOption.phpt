--TEST--
Test MultipleActiveResultSets connection setting off
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );
    sqlsrv_configure( 'LogSubsystems', SQLSRV_LOG_SYSTEM_OFF );

    require( 'MsCommon.inc' );

    $conn = Connect(array( 'MultipleActiveResultSets' => false ));
    if( $conn === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }

    $stmt1 = sqlsrv_query( $conn, "SELECT 1" );
    if( $stmt1 === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    sqlsrv_fetch( $stmt1 );

    $stmt2 = sqlsrv_query( $conn, "SELECT 2" );
    if( $stmt2 !== false ) {
        die( "Should have failed with a MARS error" );
    }
    print_r( sqlsrv_errors() );

    echo "Test succeeded.\n";
?>
--EXPECTREGEX--
Array
\(
    \[0\] => Array
        \(
            \[0\] => IMSSP
            \[SQLSTATE\] => IMSSP
            \[1\] => \-44
            \[code\] => \-44
            \[2\] => The connection cannot process this operation because there is a statement with pending results\.  To make the connection available for other queries, either fetch all results or cancel or free the statement\.  For more information, see the product documentation about the MultipleActiveResultSets connection option\.
            \[message\] => The connection cannot process this operation because there is a statement with pending results\.  To make the connection available for other queries, either fetch all results or cancel or free the statement\.  For more information, see the product documentation about the MultipleActiveResultSets connection option\.
        \)

    \[1\] => Array
        \(
            \[0\] => HY000
            \[SQLSTATE\] => HY000
            \[1\] => 0
            \[code\] => 0
            \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Connection is busy with results for another command
            \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]Connection is busy with results for another command
        \)

\)
Test succeeded\.