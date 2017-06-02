--TEST--
data corruption fix.
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

    $tsql = "SELECT int_type FROM [test_types]";

    $stmt1 = sqlsrv_query($conn, $tsql);
    if( $stmt1 === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    $row = sqlsrv_fetch_array($stmt1);
    echo $row[0] . "\n";

    $stmt2 = sqlsrv_query($conn, $tsql);
    if( $stmt2 === false ) {
        die( print_r( sqlsrv_errors(), true ));
    }
    sqlsrv_fetch($stmt2);
    echo sqlsrv_get_field($stmt2, 0) . "\n";

?>
--EXPECT--
2147483647
2147483647

