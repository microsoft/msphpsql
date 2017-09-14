--TEST--
Test errorInfo when prepare with and without emulate prepare
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once 'MsSetup.inc';

$conn = new PDO("sqlsrv:server=$server;Database=$databaseName", $uid, $pwd, array(PDO::ATTR_ERRMODE => PDO::ERRMODE_WARNING));

//drop, create and insert
$conn->query("IF OBJECT_ID('dbo.test_table', 'U') IS NOT NULL DROP TABLE dbo.test_table");
$conn->query("CREATE TABLE [dbo].[test_table](c1 int, c2 int)");
$conn->query("INSERT INTO [dbo].[test_table] VALUES (1, 10)");
$conn->query("INSERT INTO [dbo].[test_table] VALUES (2, 20)");

echo "\n****testing with emulate prepare****\n";
$stmt = $conn->prepare("SELECT c2 FROM test_table WHERE c1= :int", array(PDO::ATTR_EMULATE_PREPARES => true));

$int_col = 1;
//bind param with the wrong parameter name to test for errorInfo
$stmt->bindParam(':in', $int_col);

$stmt->execute();

echo "Statement error info:\n";
print_r($stmt->errorInfo());

if ($stmt->errorInfo()[1] == NULL && $stmt->errorInfo()[2] == NULL) {
	echo "stmt native code and native message are NULL.\n";
}
else {
	echo "stmt native code and native message should be NULL.\n";
}

echo "Connection error info:\n";
print_r($conn->errorInfo());

if ($conn->errorInfo()[1] == NULL && $conn->errorInfo()[2] == NULL) {
	echo "conn native code and native message are NULL.\n";
}
else {
	echo "conn native code and native message shoud be NULL.\n";
}

echo "\n****testing without emulate prepare****\n";
$stmt2 = $conn->prepare("SELECT c2 FROM test_table WHERE c1= :int", array(PDO::ATTR_EMULATE_PREPARES => false));

$int_col = 2;
//bind param with the wrong parameter name to test for errorInfo
$stmt2->bindParam(':it', $int_col);

$stmt2->execute();

echo "Statement error info:\n";
print_r($stmt2->errorInfo());

echo "Connection error info:\n";
print_r($conn->errorInfo());

$conn->query("IF OBJECT_ID('dbo.test_table', 'U') IS NOT NULL DROP TABLE dbo.test_table");
$stmt = NULL;
$stmt2 = NULL;
$conn = NULL;
?>
--EXPECTREGEX--
\*\*\*\*testing with emulate prepare\*\*\*\*

Warning: PDOStatement::execute\(\): SQLSTATE\[HY093\]: Invalid parameter number: parameter was not defined in .+(\/|\\)pdo_errorinfo_emulateprepare\.php on line [0-9]+

Warning: PDOStatement::execute\(\): SQLSTATE\[HY093\]: Invalid parameter number in .+(\/|\\)pdo_errorinfo_emulateprepare\.php on line [0-9]+
Statement error info:
Array
\(
    \[0\] => HY093
    \[1\] => 
    \[2\] => 
\)
stmt native code and native message are NULL\.
Connection error info:
Array
\(
    \[0\] => 00000
    \[1\] => 
    \[2\] => 
\)
conn native code and native message are NULL\.

\*\*\*\*testing without emulate prepare\*\*\*\*

Warning: PDOStatement::bindParam\(\): SQLSTATE\[HY093\]: Invalid parameter number: parameter was not defined in .+(\/|\\)pdo_errorinfo_emulateprepare\.php on line [0-9]+

Warning: PDOStatement::execute\(\): SQLSTATE\[07002\]: COUNT field incorrect: 0 \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]COUNT field incorrect or syntax error in .+(\/|\\)pdo_errorinfo_emulateprepare\.php on line [0-9]+
Statement error info:
Array
\(
    \[0\] => 07002
    \[1\] => 0
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]COUNT field incorrect or syntax error
\)
Connection error info:
Array
\(
    \[0\] => 00000
    \[1\] => 
    \[2\] => 
\)