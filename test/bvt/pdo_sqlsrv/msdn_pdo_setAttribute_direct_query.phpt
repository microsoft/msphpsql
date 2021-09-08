--TEST--
sets to PDO::SQLSRV_ATTR_DIRECT_QUERY
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    require('connect.inc');
    $conn = new PDO("sqlsrv:Server=$server; Database = $databaseName", $uid, $pwd);
    $conn->setAttribute(PDO::SQLSRV_ATTR_DIRECT_QUERY, true);

    $tableName = 'pdo_direct_query';
    $conn->query("CREATE TABLE $tableName ([c1_int] int, [c2_int] int)");

    $v1 = 1;
    $v2 = 2;

    $stmt = $conn->prepare("INSERT INTO $tableName (c1_int, c2_int) VALUES (:var1, :var2)");

    if ($stmt) {
      $stmt->bindValue(1, $v1);
      $stmt->bindValue(2, $v2);

      if ($stmt->execute()) {
         echo "Execution succeeded\n";
      } else {
         echo "Execution failed\n";
      }
    } else {
      var_dump($conn->errorInfo());
    }

    $stmt = $conn->query("DROP TABLE $tableName");

    // free the statements and connection
    unset($stmt);
    unset($conn);
?>
--EXPECT--
Execution succeeded