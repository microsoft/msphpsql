--TEST--
uses an input/output parameter
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
   require('connect.inc');
   $dbh = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

   dropProc($dbh, 'sp_ReverseString');
   $dbh->query("CREATE PROCEDURE sp_ReverseString @String as VARCHAR(2048) OUTPUT as SELECT @String = REVERSE(@String)");
   $stmt = $dbh->prepare("EXEC sp_ReverseString ?");
   $string = "123456789";
   $stmt->bindParam(1, $string, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 2048);
   $stmt->execute();
   print "Result: ".$string;   // Expect 987654321
   
   dropProc($dbh, 'sp_ReverseString', false);
   
   //free the statement and connection
   unset($stmt);
   unset($dbh);
?>
--EXPECT--
Result: 987654321