--TEST--
starts a transaction, insert 2 rows and commit the transaction
--SKIPIF--

--FILE--
<?php
	require('connect.inc');
   //make connection and create a temporaty table in tempdb
   $conn = new PDO( "sqlsrv:Server=$server; Database = tempdb ", "$uid", "$pwd");
   $conn->exec("CREAtE TABLE Table1(col1 CHARACTER(1), col2 CHARACTER(1)) ");
   
   $conn->beginTransaction();
   $ret = $conn->exec("insert into Table1(col1, col2) values('a', 'b') ");
   $ret = $conn->exec("insert into Table1(col1, col2) values('a', 'c') ");
   
   //revert the inserts
   $ret = $conn->exec("delete from Table1 where col1 = 'a'");
   $conn->commit();
   // $conn->rollback();
   echo $ret." rows affected";
   
   //drop the created temp table
   $conn->exec("DROP TABLE Table1 ");
   
   //free statement and connection
   $ret=NULL;
   $conn=NULL;
?>
--EXPECT--
2 rows affected