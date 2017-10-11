--TEST--
Test for stream zombifying.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once("MsCommon.inc");

    $conn = connect();
    if (!$conn) {
        fatalError("Failed to connect.");
    }

    $stmt = sqlsrv_query($conn, "SELECT * FROM [test_streamable_types]");
    $metadata = sqlsrv_field_metadata($stmt);
    $count = count($metadata);
    sqlsrv_fetch($stmt);
    $stream = sqlsrv_get_field($stmt, 0, SQLSRV_PHPTYPE_STREAM("binary"));
    sqlsrv_fetch($stmt);
    $name = fread($stream, 100);
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

?>
--EXPECTREGEX--
Warning: fread\(\): supplied resource is not a valid stream resource in .+(\/|\\)test_stream\.php on line [0-9]+
