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

  $conn = connect();
  if (!$conn) {
      fatalError("Failed to connect");
  }

  $stmt = sqlsrv_query($conn, "IF OBJECT_ID('php_table_1_SERIL3', 'U') IS NOT NULL DROP TABLE [php_table_1_SERIL3]");
  if ($stmt !== false) {
      sqlsrv_free_stmt($stmt);
  }

  $stmt = sqlsrv_query($conn, "CREATE TABLE [php_table_1_SERIL3] ( [TestColumn] VARCHAR (8000) NULL )");
  if ($stmt === false) {
      die(print_r(sqlsrv_errors(), true));
  }
  sqlsrv_free_stmt($stmt);

  $connectionInfo = [
	'Database' 				   => $database,
	'UID' 					   => $userName,
	'PWD' 					   => $userPassword,
	'CharacterSet'             => 'UTF-8',
	'LoginTimeout'             => 30,
	'MultipleActiveResultSets' => 0,
	'TrustServerCertificate'   => 1,
	'Encrypt'                  => 0,
  ];
  
  $handle= sqlsrv_connect($server, $connectionInfo);
  $query = "INSERT INTO [php_table_1_SERIL3] (TestColumn) VALUES (?)";
  $parameterValue = "Test value.";
  $parameterReference[0] = [ & $parameterValue, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR('8000') ];
  $queryHandle = sqlsrv_prepare($handle, $query, $parameterReference);

  $r1 = sqlsrv_execute($queryHandle);
  if ($r1 === false) {  
    print_r(sqlsrv_errors(SQLSRV_ERR_ALL));
  }

  $r2 = sqlsrv_execute($queryHandle);
  if ($r2 === false) {
    print_r(sqlsrv_errors(SQLSRV_ERR_ALL));
  }

  $stmt = sqlsrv_query($conn, "DROP TABLE [php_table_1_SERIL3]");
  sqlsrv_free_stmt($stmt);
  sqlsrv_close($conn);

  echo "Done\n";

?>
--EXPECT--
Done
