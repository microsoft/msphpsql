--TEST--
prepare with emulate prepare and binding binary parameters
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('MsSetup.inc');
$connection_options = array();
$connection_options[PDO::ATTR_ERRMODE] = PDO::ERRMODE_EXCEPTION;
$connection_options[PDO::ATTR_STRINGIFY_FETCHES] = true;
$cnn = new PDO("sqlsrv:server=$server;Database=$databaseName", $uid, $pwd, $connection_options);

$pdo_options = array();
$pdo_options[PDO::ATTR_CURSOR] = PDO::CURSOR_SCROLL;
$pdo_options[PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE] = PDO::SQLSRV_CURSOR_BUFFERED;

//Drop
try {
	$st = $cnn->prepare('DROP TABLE TESTTABLE');
	$st->execute();
}
catch(Exception $e) {}

//Recreate
$st = $cnn->prepare('CREATE TABLE TESTTABLE ([COLA] varbinary(max))');
$st->execute();

$p = fopen('php://memory', 'a');
fwrite($p, 'asdgasdgasdgsadg');
rewind($p);

//WORKS OK without emulate prepare
print_r("Prepare without emulate prepare:\n");
$st = $cnn->prepare('INSERT INTO TESTTABLE VALUES(:p0)', $pdo_options);
$st->bindParam(':p0', $p, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
$st->execute();

$st = $cnn->prepare('SELECT TOP 1 * FROM TESTTABLE', $pdo_options);
$st->execute();
$value = $st->fetch(PDO::FETCH_ASSOC);
print_r($value);

//EMULATE PREPARE with SQLSRV_ENCODING_BINARY
$pdo_options[PDO::ATTR_EMULATE_PREPARES] = TRUE;
print_r("Prepare with emulate prepare and set encoding to binary:\n");
$st = $cnn->prepare('INSERT INTO TESTTABLE VALUES(:p0)', $pdo_options);
$st->bindParam(':p0', $p, PDO::PARAM_LOB, 0, PDO::SQLSRV_ENCODING_BINARY);
$st->execute();

$st = $cnn->prepare('SELECT * FROM TESTTABLE', $pdo_options);
$st->execute();
$value = $st->fetch(PDO::FETCH_ASSOC);
print_r($value);

//EMULATE PREPARE with no bind param options
print_r("Prepare with emulate prepare and no bindparam options:\n");
$st = $cnn->prepare('INSERT INTO TESTTABLE VALUES(:p0)', $pdo_options);
$st->bindParam(':p0', $p, PDO::PARAM_LOB);
$st->execute();

$st = $cnn->prepare('SELECT * FROM TESTTABLE', $pdo_options);
$st->execute();
$value = $st->fetch(PDO::FETCH_ASSOC);
print_r($value);

$st = null;
$cnn = null;
?>
--EXPECTREGEX--
Prepare without emulate prepare:
Array
\(
    \[COLA\] => asdgasdgasdgsadg
\)
Prepare with emulate prepare and set encoding to binary:
Array
\(
    \[COLA\] => asdgasdgasdgsadg
\)
Prepare with emulate prepare and no bindparam options:

Fatal error: Uncaught PDOException: SQLSTATE\[42000\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Implicit conversion from data type varchar to varbinary\(max\) is not allowed\. Use the CONVERT function to run this query\. in .+(\/|\\)pdo_prepare_emulatePrepare_binary\.php:[0-9]+
Stack trace:
#0 .+(\/|\\)pdo_prepare_emulatePrepare_binary\.php\([0-9]+\): PDOStatement->execute\(\)
#1 {main}
  thrown in .+(\/|\\)pdo_prepare_emulatePrepare_binary\.php on line [0-9]+