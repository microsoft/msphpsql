--TEST--
uses an input/output parameter
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

$dbh = connect();

DropProc( $dbh, "sp_ReverseString" );
$dbh->query("CREATE PROCEDURE dbo.sp_ReverseString @String as VARCHAR(2048) OUTPUT as SELECT @String = REVERSE(@String)");
$stmt = $dbh->prepare("EXEC dbo.sp_ReverseString ?");
$string = "123456789";
$stmt->bindParam(1, $string, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 2048);
$stmt->execute();
print "Result: ".$string."\n";   // Expect 987654321

//free the statement and connection
$stmt = null;
$dbh = null;
?>
--EXPECT--
Result: 987654321
