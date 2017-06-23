--TEST--
for a condition that was causing a crash when calling sqlsrv_errors after an invalid query.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );

    $conn = Connect();
    if (!$conn) {
        echo "connection failed:";
        echo "<pre>".print_r(sqlsrv_errors(),true)."</pre>";
        exit;
    }

    if (!sqlsrv_query($conn,"INVALID QUERY")) {
        echo "invalid statement failed:<br>";
        echo "errors: <pre>".print_r(sqlsrv_errors(),true)."</pre><br>";
        echo "warnings: <pre>".print_r(sqlsrv_errors( SQLSRV_ERR_WARNINGS ),true)."</pre>";
    }

?>
--EXPECTF--
invalid statement failed:<br>errors: <pre>Array
(
    [0] => Array
        (
            [0] => 42000
            [SQLSTATE] => 42000
            [1] => 2812
            [code] => 2812
            [2] => %SCould not find stored procedure 'INVALID'.
            [message] => %SCould not find stored procedure 'INVALID'.
        )

)
</pre><br>warnings: <pre></pre>

