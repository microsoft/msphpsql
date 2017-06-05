--TEST--
fetches the next row as an object
--SKIPIF--

--FILE--
<?php
	require('connect.inc');
   $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");

   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetchObject();
   print $result->Name;
   
   //free the statement and connection 
   $stmt=null;
   $conn=null;
?>
--EXPECT--
Accounting Manager