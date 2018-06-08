--TEST--
UTF-8 connection strings
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsSetup.inc");
require_once("MsCommon_mid-refactor.inc");

$server = 'localhost';
$databaseName = 'test';
$uid = 'sa';
$pwd = 'Sunshine4u';

$dsn = getDSN($server, $databaseName, $driver);
// test an invalid connection credentials
$c = new PDO($dsn, $uid, $pwd);

if ($c !== false) {
    die("Should have failed to connect.");
}
?>
--EXPECTREGEX--

Fatal error: Uncaught PDOException: SQLSTATE\[(28000|08001|HYT00)\]: .*\[Microsoft\]\[ODBC Driver 1[0-9] for SQL Server\](\[SQL Server\])?(Named Pipes Provider: Could not open a connection to SQL Server \[2\]\. |TCP Provider: Error code (0x2726|0x2AF9)|Login timeout expired|Login failed for user 'sa'\.) in .+(\/|\\)pdo_utf8_conn\.php:[0-9]+
Stack trace:
#0 .+(\/|\\)pdo_utf8_conn\.php\([0-9]+\): PDO->__construct\('sqlsrv:Server=l\.\.\.', 'sa', 'Sunshine4u'\)
#1 {main}
  thrown in .+(\/|\\)pdo_utf8_conn\.php on line [0-9]+
