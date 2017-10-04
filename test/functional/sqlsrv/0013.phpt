--TEST--
A test for a simple query
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = connect();
    if (!$conn) {
        fatalError("Failed to connect");
    }
    $stmt = sqlsrv_query($conn, "SELECT * FROM [cd_info]");
    if (! $stmt) {
        fatalError("Failed to select from cd_info");
    }

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);

    echo "Test successful<br/>\n";
?>
--EXPECT--
Test successful<br/>
