--TEST--
execute a delete and reports how many rows were deleted
--SKIPIF--

--FILE--
<?php
   require('connect.inc');
   $c = new PDO( "sqlsrv:Server=$server", "$uid", "$pwd");

   $c->exec("use tempdb");
   $c->exec("CREAtE TABLE Table1(col1 VARCHAR(100), col2 VARCHAR(100)) ");
   
   $ret = $c->exec("insert into Table1 values('xxxyy', 'yyxx')");
   $ret = $c->exec("delete from Table1 where col1 = 'xxxyy'");
   echo $ret," rows affected";
   
   $c->exec("DROP TABLE Table1 ");
   
   //free the statement and connection
   $ret=null;
   $c=null;
?>
--EXPECT--
1 rows affected