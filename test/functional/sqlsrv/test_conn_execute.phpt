--TEST--
crash caused by a statement being orphaned when an error occurred during sqlsrv_conn_execute.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );

    $conn1 = Connect();
    $stmt1 = sqlsrv_query($conn1, "SELECT * FROM Servers");
    sqlsrv_close($conn1);
    $row1 = sqlsrv_fetch_array($stmt1);
    $conn3 = Connect();

    echo "Test successful\n";

?> 
--EXPECTREGEX--
Warning: sqlsrv_fetch_array\(\) expects parameter 1 to be resource, boolean given in .+(\/|\\)test_conn_execute\.php on line 11
Test successful
