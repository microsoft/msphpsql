--TEST--
Bug #144 (PHP-7.0-Linux sqlsrv_query() produces floating point exception when bind parameter is a empty string)
--SKIPIF--
<?php if(!extension_loaded("sqlsrv")) print "skip"; ?>
--INI--
--FILE--
<?php
require('config.inc');

$conn = sqlsrv_connect($serverName, ['Database' => $database, 'Uid' => $username, 'PWD' => $password]);
print 'sqlsrv connection successfull: '.($conn !== false ? 'yes' : 'no').PHP_EOL;

$result = sqlsrv_query($conn, 'SELECT ?', ['']);
print 'sqlsrv parametrized query successfull: '.($result !== false ? 'yes' : 'no').PHP_EOL;

$row = sqlsrv_fetch_array($result, SQLSRV_FETCH_NUMERIC);
print 'sqlsrv parametrized query result 1st colum is empty string: ' . ($row[0] === '' ? 'yes' : 'no') . PHP_EOL;

sqlsrv_free_stmt($result);

?>
--EXPECT--
sqlsrv connection successfull: yes
sqlsrv parametrized query successfull: yes
sqlsrv parametrized query result 1st colum is empty string: yes
