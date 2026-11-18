--TEST--
Request initialization converts negative numeric INI values without textual boolean parsing
--SKIPIF--
<?php require('skipif.inc'); ?>
--INI--
sqlsrv.WarningsReturnAsErrors=-1
sqlsrv.LogSeverity=-1
sqlsrv.LogSubsystems=0
sqlsrv.ClientBufferMaxKBSize=10240
--FILE--
<?php
var_dump(sqlsrv_get_config('WarningsReturnAsErrors'));
var_dump(sqlsrv_get_config('LogSeverity'));
var_dump(sqlsrv_get_config('ClientBufferMaxKBSize'));
?>
--EXPECT--
bool(true)
int(-1)
int(10240)
