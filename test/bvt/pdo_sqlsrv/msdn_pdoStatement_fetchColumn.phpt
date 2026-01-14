--TEST--
fetches a column in a row
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
   require('connect.inc');
   $conn = getPdoConnection();
	
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