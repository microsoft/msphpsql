--TEST--
crash caused by a statement being orphaned when an error occurred during sqlsrv_conn_execute.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    // Include driver warnings in the same assertions as argument errors.
    function warningHandler($errno, $errstr) 
    { 
        throw new TypeError($errstr);
    }
    
    function compareMessages($err, $expected)
    {
        if (!fnmatch($expected, $err->getMessage())) {
            echo $err->getMessage() . PHP_EOL;
        }
    }

    set_error_handler("warningHandler", E_WARNING);

    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );
    try {
        $conn1 = Connect();
        $stmt1 = sqlsrv_query($conn1, "SELECT * FROM Servers");
        sqlsrv_close($conn1);
        $row1 = sqlsrv_fetch_array($stmt1);
        $conn3 = Connect();
    } catch (TypeError $e) {
        compareMessages($e, 
                        "sqlsrv_fetch_array(): Argument #1 (\$stmt) must be of type resource, false given");
    }

    echo "Done\n";

?> 
--EXPECT--
Done
