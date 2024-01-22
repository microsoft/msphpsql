--TEST--
HY104 Invalid precision value when reusing prepared statement
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
  set_time_limit(0);
  sqlsrv_configure('WarningsReturnAsErrors', 0);
  sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

  require_once('MsCommon.inc');
 
  $conn = connect(array('CharacterSet'=>'UTF-8'));
  if (!$conn) {
      fatalError("Failed to connect");
  }

  $tableName = 'php_test_table';
  $column = array(new AE\ColumnMeta("VARCHAR(8000)", "TestColumn", "NULL"));
  
  $stmt = AE\createTable($conn, $tableName, $column);
  if (!$stmt) {
      fatalError("Failed to create table $tableName\n");
  }

  $query = "INSERT INTO $tableName (TestColumn) VALUES (?)";
  $parameterValue = "Test value.";
  $parameterReference[0] = [ & $parameterValue, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('8000') ];
  $queryHandle = sqlsrv_prepare($conn, $query, $parameterReference);

  $r1 = sqlsrv_execute($queryHandle);
  if ($r1 === false) {  
    print_r(sqlsrv_errors(SQLSRV_ERR_ALL));
  }

  $r2 = sqlsrv_execute($queryHandle);
  if ($r2 === false) {
    print_r(sqlsrv_errors(SQLSRV_ERR_ALL));
  }

  dropTable($conn, $tableName);
  sqlsrv_close($conn);

  echo "Done\n";

?>
--EXPECT--
Done
