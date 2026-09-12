--TEST--
Request initialization preserves numeric INI parsing and typed configuration values
--SKIPIF--
<?php require('skipif.inc'); ?>
--INI--
sqlsrv.WarningsReturnAsErrors=08
sqlsrv.LogSeverity=0x4
sqlsrv.LogSubsystems=0
sqlsrv.ClientBufferMaxKBSize=02000
--FILE--
<?php
var_dump(sqlsrv_get_config('WarningsReturnAsErrors'));
var_dump(sqlsrv_get_config('LogSeverity'));
var_dump(sqlsrv_get_config('LogSubsystems'));
var_dump(sqlsrv_get_config('ClientBufferMaxKBSize'));
var_dump(sqlsrv_configure('WarningsReturnAsErrors', true));
var_dump(sqlsrv_get_config('WarningsReturnAsErrors'));
var_dump(sqlsrv_configure('ClientBufferMaxKBSize', 2048));
var_dump(sqlsrv_get_config('ClientBufferMaxKBSize'));
?>
--EXPECT--
bool(false)
int(4)
int(0)
int(1024)
bool(true)
bool(true)
bool(true)
int(2048)
