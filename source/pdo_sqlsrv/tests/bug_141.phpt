--TEST--
Bug #141 (Assinging empty string as bind parameter produces floating point exception)
--SKIPIF--
<?php if(!extension_loaded("pdo_sqlsrv")) print "skip"; ?>
--INI--
--FILE--
<?php
require('config.inc');

$dbo = new PDO("sqlsrv:server=$serverName ; Database = $database", $username, $password);
print 'PDO sqlsrv connection successfull: '.($dbo ? 'yes' : 'no').PHP_EOL;

$sth = $dbo->prepare('select ?');
print 'PDO sqlsrv prepare successfull: '.($sth !== false ? 'yes' : 'no').PHP_EOL;

$result = $sth->bindValue(1, '');
print 'PDO sqlsrv value binding successfull: '.($result !== false ? 'yes' : 'no').PHP_EOL;

$result = $sth->execute();
print 'PDO sqlsrv execute successfull: '.($result !== false ? 'yes' : 'no').PHP_EOL;

$row = $sth->fetch(PDO::FETCH_NUM);
print 'PDO sqlsrv fetch result 1st column is empty string: ' . ($row[0] === '' ? 'yes' : 'no') . PHP_EOL;
?>
--EXPECT--
PDO sqlsrv connection successfull: yes
PDO sqlsrv prepare successfull: yes
PDO sqlsrv value binding successfull: yes
PDO sqlsrv execute successfull: yes
PDO sqlsrv fetch result 1st column is empty string: yes
