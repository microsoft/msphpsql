--TEST--
Bug #119 (sqlsrv_fetch_object modifies full qualified class names)
--SKIPIF--
<?php if(!extension_loaded("sqlsrv")) print "skip"; ?>
--INI--
--FILE--
<?php
namespace Tests;

class A {
    public $tname;
}

require('config.inc');

$conn = sqlsrv_connect($serverName, ['Database' => $database, 'Uid' => $username, 'PWD' => $password]);
print 'sqlsrv connection successfull: '.($conn !== false ? 'yes' : 'no').PHP_EOL;

$idx = array_search(__NAMESPACE__.'\\A',get_declared_classes());
echo "BEFORE sqlsrv_fetch_object - ";
echo "orig class ".__NAMESPACE__.'\\A'." exits: ".(class_exists(__NAMESPACE__.'\\A')?'yes':'no').PHP_EOL;
echo "BEFORE sqlsrv_fetch_object - ";
echo "lower class ".strtolower(__NAMESPACE__.'\\A')." exits: ".(class_exists(strtolower(__NAMESPACE__.'\\A'))?'yes':'no').PHP_EOL;
echo "BEFORE sqlsrv_fetch_object - ";
echo 'full qualified class name: '.get_declared_classes()[$idx].PHP_EOL;

$stmt = sqlsrv_query($conn, "SELECT 'foo' as tname");
print 'sqlsrv query successfull: '.($stmt !== false ? 'yes' : 'no').PHP_EOL;

$obj = sqlsrv_fetch_object($stmt, __NAMESPACE__.'\\A');

echo "AFTER sqlsrv_fetch_object - ";
echo "orig class ".__NAMESPACE__.'\\A'." exits: ".(class_exists(__NAMESPACE__.'\\A')?'yes':'no').PHP_EOL;
echo "AFTER sqlsrv_fetch_object - ";
echo "lower class ".strtolower(__NAMESPACE__.'\\A')." exits: ".(class_exists(strtolower(__NAMESPACE__.'\\A'))?'yes':'no').PHP_EOL;
echo "AFTER sqlsrv_fetch_object - ";
echo 'full qualified class name: '.get_declared_classes()[$idx].PHP_EOL;
sqlsrv_free_stmt($stmt);
echo 'object has the correct value: '.$obj->tname.PHP_EOL;
?>
--EXPECT--
sqlsrv connection successfull: yes
BEFORE sqlsrv_fetch_object - orig class Tests\A exits: yes
BEFORE sqlsrv_fetch_object - lower class tests\a exits: yes
BEFORE sqlsrv_fetch_object - full qualified class name: Tests\A
sqlsrv query successfull: yes
AFTER sqlsrv_fetch_object - orig class Tests\A exits: yes
AFTER sqlsrv_fetch_object - lower class tests\a exits: yes
AFTER sqlsrv_fetch_object - full qualified class name: Tests\A
object has the correct value: foo
