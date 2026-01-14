--TEST--
fetches the next row as an object
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
	require('connect.inc');
   $conn = getPdoConnection();

   $stmt = $conn->query( "select * from Person.ContactType where ContactTypeID < 5 " );
   $result = $stmt->fetchObject();
   print $result->Name;
   
   //free the statement and connection 
   $stmt=null;
   $conn=null;
?>
--EXPECT--
Accounting Manager