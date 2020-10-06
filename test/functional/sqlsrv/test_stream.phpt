--TEST--
Test for stream zombifying.
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
    
    set_error_handler("warningHandler", E_WARNING);

    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once("MsCommon.inc");

    $conn = connect();
    if (!$conn) {
        fatalError("Failed to connect.");
    }

    $stmt = sqlsrv_query($conn, "SELECT * FROM sys.objects");
    $metadata = sqlsrv_field_metadata($stmt);
    $count = count($metadata);
    sqlsrv_fetch($stmt);
    $stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM("binary"));
    sqlsrv_fetch($stmt);
    
    try {
        $name = fread($stream, 100);
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

?>
--EXPECT--
fread(): supplied resource is not a valid stream resource