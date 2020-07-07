--TEST--
sets to PDO::SQLSRV_ATTR_DIRECT_QUERY
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require('connect.inc');	
    $conn = new PDO("sqlsrv:Server=$server", "$uid", "$pwd");
    $conn->setAttribute(constant('PDO::SQLSRV_ATTR_DIRECT_QUERY'), true);

    $tableName = 'pdo_direct_query';
    $tsql = "IF EXISTS (SELECT * FROM sys.objects WHERE object_id = OBJECT_ID(N'" . $tableName . "') AND type in (N'U')) DROP TABLE $tableName";

    $stmt1 = $conn->query($tsql);
    $stmt2 = $conn->query("CREATE TABLE $tableName ([c1_int] int, [c2_int] int)");

    $v1 = 1;
    $v2 = 2;

    $stmt3 = $conn->prepare("INSERT INTO $tableName (c1_int, c2_int) VALUES (:var1, :var2)");

    if ($stmt3) {
      $stmt3->bindValue(1, $v1);
      $stmt3->bindValue(2, $v2);

      if ($stmt3->execute()) {
         echo "Execution succeeded\n";     
      } else {
         echo "Execution failed\n";
      }
    } else {
      var_dump($conn->errorInfo());
    }

    $stmt4 = $conn->query("DROP TABLE $tableName");

    // free the statements and connection
    unset($stmt1);
    unset($stmt2);
    unset($stmt3);
    unset($stmt4);
    unset($conn);
?>
--EXPECT--
Execution succeeded