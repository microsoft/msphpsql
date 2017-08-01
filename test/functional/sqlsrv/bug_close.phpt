--TEST--
crash in sqlsrv_close when followed by sqlsrv_query
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require( 'MsCommon.inc' );
    $conn = Connect();

    if (!$conn) {
        echo "connection failed:";
        echo "<pre>".print_r(sqlsrv_errors(),true)."</pre>";
        exit;
    }

    //any query seems to trigger the bug
    $select = 'SELECT @@IDENTITY;';
    $stmt = sqlsrv_query($conn, $select);
    echo "Test successful".PHP_EOL;
    sqlsrv_close($conn);

?>
--EXPECTF--
Test successful
