--TEST--
sets to PDO::SQLSRV_ATTR_DIRECT_QUERY
--SKIPIF--

--FILE--
<?php
   require('connect.inc');	
   $conn = new PDO("sqlsrv:Server=$server", "$uid", "$pwd");
   $conn->setAttribute(constant('PDO::SQLSRV_ATTR_DIRECT_QUERY'), true);

   $stmt1 = $conn->query("DROP TABLE #php_test_table");

   $stmt2 = $conn->query("CREATE TABLE #php_test_table ([c1_int] int, [c2_int] int)");

   $v1 = 1;
   $v2 = 2;

   $stmt3 = $conn->prepare("INSERT INTO #php_test_table (c1_int, c2_int) VALUES (:var1, :var2)");

   if ($stmt3) {
      $stmt3->bindValue(1, $v1);
      $stmt3->bindValue(2, $v2);

      if ($stmt3->execute())
         echo "Execution succeeded\n";     
      else
         echo "Execution failed\n";
   }
   else
      var_dump($conn->errorInfo());

   $stmt4 = $conn->query("DROP TABLE #php_test_table");

   // free the statements and connection
   $stmt1=null;
   $stmt2=null;
   $stmt3=null;
   $stmt4=null;
   $conn=null;
   ?>
--EXPECT--
Execution succeeded