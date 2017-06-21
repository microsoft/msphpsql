--TEST--
fetches a column in a row
--SKIPIF--

--FILE--
<?php
   require('connect.inc');
   $conn = new PDO( "sqlsrv:server=$server ; Database = $databaseName", "$uid", "$pwd");
	
   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   while ( $result = $stmt->fetchColumn(1)) { 
      print($result . "\n"); 
   }
   
   //free the statement and connection 
   $stmt=null;
   $conn=null;
?>
--EXPECT--
Accounting Manager
Assistant Sales Agent
Assistant Sales Representative
Coordinator Foreign Markets