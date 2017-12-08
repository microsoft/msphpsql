--TEST--
test input param with unknown encoding
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);

require_once('MsCommon.inc');

$conn = AE\connect();
$tableName = 'php_table_SERIL1_1';
$columns = array(new AE\ColumnMeta('int', 'c1_int'),
                 new AE\ColumnMeta('varchar(max)', 'c2_varchar_max'));
$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}

$errState = 'IMSSP';
$errMessage = 'An invalid PHP type for parameter 2 was specified.';

$intType = AE\isColEncrypted() ? SQLSRV_SQLTYPE_INT : null;
$stmt = sqlsrv_query($conn, "INSERT INTO [php_table_SERIL1_1] (c1_int, c2_varchar_max) VALUES (?, ?)", array(array(1, null, null, $intType),
      array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UNKNOWN), null)));
if ($stmt !== false) {
    sqlsrv_free_stmt($stmt);
    die("sqlsrv_query shouldn't have succeeded.");
}

verifyError(sqlsrv_errors()[0], $errState, $errMessage);

$stmt = sqlsrv_prepare($conn, "INSERT INTO [php_table_SERIL1_1] (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UNKNOWN), null)));
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
$result = sqlsrv_execute($stmt);
if ($result !== false) {
    sqlsrv_free_stmt($stmt);
    die("sqlsrv_execute shouldn't have succeeded.");
}

verifyError(sqlsrv_errors()[0], $errState, $errMessage);

sqlsrv_query($conn, "DROP TABLE [php_table_SERIL1_1]");

sqlsrv_close($conn);
 
?>
--EXPECTREGEX--
(Warning|Notice)\: Use of undefined constant SQLSRV_ENC_UNKNOWN - assumed \'SQLSRV_ENC_UNKNOWN\' (\(this will throw an Error in a future version of PHP\) )?in .+(\/|\\)sqlsrv_input_param_unknown_encoding\.php on line 24

(Warning|Notice)\: Use of undefined constant SQLSRV_ENC_UNKNOWN - assumed \'SQLSRV_ENC_UNKNOWN\' (\(this will throw an Error in a future version of PHP\) )?in .+(\/|\\)sqlsrv_input_param_unknown_encoding\.php on line 32
