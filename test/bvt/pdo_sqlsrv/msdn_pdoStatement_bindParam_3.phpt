--TEST--
uses an input/output parameter
--SKIPIF--

--FILE--
<?php
   require('connect.inc');
   $dbh = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

   $dbh->query("IF OBJECT_ID('dbo.sp_ReverseString', 'P') IS NOT NULL DROP PROCEDURE dbo.sp_ReverseString");
   $dbh->query("CREATE PROCEDURE dbo.sp_ReverseString @String as VARCHAR(2048) OUTPUT as SELECT @String = REVERSE(@String)");
   $stmt = $dbh->prepare("EXEC dbo.sp_ReverseString ?");
   $string = "123456789";
   $stmt->bindParam(1, $string, PDO::PARAM_STR | PDO::PARAM_INPUT_OUTPUT, 2048);
   $stmt->execute();
   print "Result: ".$string;   // Expect 987654321
   
   //free the statement and connection
   $stmt = null;
   $dbh = null;
?>
--EXPECT--
Result: 987654321