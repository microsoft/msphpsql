--TEST--
crash caused by a statement being orphaned when an error occurred during sqlsrv_conn_execute.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    // When testing with PHP 8.0 it throws a TypeError instead of a warning. Thus implement a custom 
    // warning handler such that with PHP 7.x the warning would be handled to throw a TypeError.
    // Sometimes the error messages from PHP 8.0 may be different and have to be handled differently.
    function warningHandler($errno, $errstr) 
    { 
        throw new TypeError($errstr);
    }
    
    function compareMessages($err, $exp8x, $exp7x) 
    {
        $expected = (PHP_MAJOR_VERSION == 8) ? $exp8x : $exp7x;
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
                        "sqlsrv_fetch_array(): Argument #1 (\$stmt) must be of type resource, bool given", 
                        "sqlsrv_fetch_array() expects parameter 1 to be resource, bool* given");       
    }

    echo "Done\n";

?> 
--EXPECT--
Done
