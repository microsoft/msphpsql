--TEST--
Test sqlsrv_get_config method.
--SKIPIF--
<?php require 'skipif.inc'; ?>
--FILE--
<?php
sqlsrv_configure('WarningsReturnAsError', 0);

sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_ALL);
$sql_get = sqlsrv_get_config('LogSubsystems');
echo "Get Config LogSubsystems " . $sql_get . "\n";
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_CONN);
$sql_get = sqlsrv_get_config('LogSubsystems');
echo "Get Config LogSubsystems " . $sql_get . "\n";
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_INIT);
$sql_get = sqlsrv_get_config('LogSubsystems');
echo "Get Config LogSubsystems " . $sql_get . "\n";
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_STMT);
$sql_get = sqlsrv_get_config('LogSubsystems');
echo "Get Config LogSubsystems " . $sql_get . "\n";
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_UTIL);
$sql_get = sqlsrv_get_config('LogSubsystems');
echo "Get Config LogSubsystems " . $sql_get . "\n";
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);
$sql_get = sqlsrv_get_config('LogSubsystems');
echo "Get Config LogSubsystems " . $sql_get . "\n";

sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
echo "Get Config LogSeverity " . $sql_get . "\n";
$sql_get = sqlsrv_get_config('LogSeverity');
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ERROR);
echo "Get Config LogSeverity " . $sql_get . "\n";
$sql_get = sqlsrv_get_config('LogSeverity');
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
echo "Get Config LogSeverity " . $sql_get . "\n";
$sql_get = sqlsrv_get_config('LogSeverity');
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_NOTICE);
echo "Get Config LogSeverity " . $sql_get . "\n";
$sql_get = sqlsrv_get_config('LogSeverity');
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_WARNING);
echo "Get Config LogSeverity " . $sql_get . "\n";
$sql_get = sqlsrv_get_config('LogSeverity');

$sql_get = sqlsrv_get_config('ClientBufferMaxKBSize');
echo "Get buffer size " . $sql_get . "\n"; 

?>
--EXPECT--
Get Config LogSubsystems -1
Get Config LogSubsystems 2
Get Config LogSubsystems 1
Get Config LogSubsystems 4
Get Config LogSubsystems 8
Get Config LogSubsystems 0
Get Config LogSeverity 0
Get Config LogSeverity -1
Get Config LogSeverity 1
Get Config LogSeverity -1
Get Config LogSeverity 4
Get buffer size 10240