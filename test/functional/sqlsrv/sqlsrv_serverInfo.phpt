--TEST--
Verify sqlsrv_server_info
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = connect();

    if (!$conn) {
        fatalError("Failed to connect.");
    }

    $server_info = sqlsrv_server_info($conn);
    var_dump($server_info);

    sqlsrv_close($conn);

?>
--EXPECTREGEX--
array\(3\) {
  \[\"CurrentDatabase\"\]=>
  string\([0-9]+\) \"[A-Za-z_0-9]+\"
  \[\"SQLServerVersion\"\]=>
  string\(10\) \"[0-9]{2}.[0-9]{2}.[0-9]{4}\"
  \[\"SQLServerName\"\]=>
  string\([0-9]+\) \"[-A-Za-z0-9_\\]+"
}
