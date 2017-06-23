--TEST--
Verify query timeout 
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

require( 'MsCommon.inc' );

$throwaway = Connect(array( 'ConnectionPooling' => 1 ));
if( !$throwaway ) {
    die( print_r( sqlsrv_errors(), true ));
}

for( $i = 1; $i <= 3; ++$i ) {

    $conn = Connect(array( 'ConnectionPooling' => 1 ));
    if( !$conn ) {
        die( print_r( sqlsrv_errors(), true ));
    }

    $conn2 = Connect(array( 'ConnectionPooling' => 1 ));
    if( !$conn2 ) {
      die( print_r( sqlsrv_errors(), true ));
    }

    $stmt = sqlsrv_query( $conn, "IF OBJECT_ID('test_query_timeout', 'U') IS NOT NULL DROP TABLE [test_query_timeout]");
    if( $stmt !== false ) sqlsrv_free_stmt( $stmt );

    $stmt = sqlsrv_query( $conn, "CREATE TABLE [test_query_timeout] (id int, stuff varchar(256))");
    if( $stmt === false ) {
      die( print_r( sqlsrv_errors(), true ));
    }

    sqlsrv_free_stmt( $stmt );

    $result = sqlsrv_begin_transaction( $conn );
    if( $result === false ) {
      die( print_r( sqlsrv_errors(), true ));
    }

    $stmt = sqlsrv_query( $conn, "INSERT INTO [test_query_timeout] (id, stuff) VALUES (?,?)", array( 1, 'this is a test' ));
    if( $stmt === false ) {
      die( print_r( sqlsrv_errors(), true ));
    }


    $stmt2 = sqlsrv_query( $conn2, "WAITFOR DELAY '00:00:05'; SELECT * FROM [test_query_timeout]", array(null), array( 'QueryTimeout' => 1 ));
    if( $stmt2 === false ) {
        print_r( sqlsrv_errors() );
    }

    sqlsrv_rollback( $conn );

    sqlsrv_query( $conn, "DROP TABLE [test_query_timeout]");

    sqlsrv_close( $conn2 );
    sqlsrv_close( $conn );

}  // for

sqlsrv_close( $throwaway );

echo "Test succeeded.\n";

?>
--EXPECTREGEX-- 
Array
\(
    \[0\] => Array
        \(
            ((\[0\] => 42000)|(\[0\] => HYT00))
            ((\[SQLSTATE\] => 42000)|(\[SQLSTATE\] => HYT00))
            ((\[1\] => 1222)|(\[1\] => 0))
            ((\[code\] => 1222)|(\[code\] => 0))
            ((\[2\] => .*Lock request time out period exceeded.)|(\[2\] => .*Query timeout expired))
            ((\[message\] => .*Lock request time out period exceeded.)|(\[message\] => .*Query timeout expired))
        \)

\)
Array
\(
    \[0\] => Array
        \(
            ((\[0\] => 42000)|(\[0\] => HYT00))
            ((\[SQLSTATE\] => 42000)|(\[SQLSTATE\] => HYT00))
            ((\[1\] => 1222)|(\[1\] => 0))
            ((\[code\] => 1222)|(\[code\] => 0))
            ((\[2\] => .*Lock request time out period exceeded.)|(\[2\] => .*Query timeout expired))
            ((\[message\] => .*Lock request time out period exceeded.)|(\[message\] => .*Query timeout expired))
        \)

\)
Array
\(
    \[0\] => Array
        \(
            ((\[0\] => 42000)|(\[0\] => HYT00))
            ((\[SQLSTATE\] => 42000)|(\[SQLSTATE\] => HYT00))
            ((\[1\] => 1222)|(\[1\] => 0))
            ((\[code\] => 1222)|(\[code\] => 0))
            ((\[2\] => .*Lock request time out period exceeded.)|(\[2\] => .*Query timeout expired))
            ((\[message\] => .*Lock request time out period exceeded.)|(\[message\] => .*Query timeout expired))
        \)

\)
Test succeeded.
