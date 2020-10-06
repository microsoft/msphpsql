--TEST--
test input param with unknown encoding
--DESCRIPTION--
When running this test with PHP 7.x, PHP warnings like the one below are expected
 "Warning: Use of undefined constant SQLSRV_ENC_UNKNOWN - assumed 'SQLSRV_ENC_UNKNOWN' 
 (this will throw an Error in a future version of PHP)"
When running this test with PHP 8.0, the previous warnings are now errors directly from PHP
Because PHP warnings are intercepted, no need to check sqlsrv errors for those. 
Add a new test case to check the error message 'An invalid PHP type for parameter 2 was specified.'                   
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

set_error_handler("warningHandler", E_WARNING);

function warningHandler($errno, $errstr) 
{ 
    throw new Error($errstr);
}

function compareMessages($err) 
{
    $exp8x = 'Undefined constant "SQLSRV_ENC_UNKNOWN"';
    $exp7x = "Use of undefined constant SQLSRV_ENC_UNKNOWN - assumed 'SQLSRV_ENC_UNKNOWN' (this will throw an Error in a future version of PHP)";
    
    $expected = (PHP_MAJOR_VERSION == 8) ? $exp8x : $exp7x;
    if ($err->getMessage() !== $expected) {
        echo $err->getMessage() . PHP_EOL;
    }
}

set_time_limit(0);
sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);
sqlsrv_configure('LogSubsystems', SQLSRV_LOG_SYSTEM_OFF);

require_once('MsCommon.inc');

$conn = AE\connect();
$tableName = 'table_unknown_encoding';
$columns = array(new AE\ColumnMeta('int', 'c1_int'),
                 new AE\ColumnMeta('varchar(max)', 'c2_varchar_max'));
$stmt = AE\createTable($conn, $tableName, $columns);
if (!$stmt) {
    fatalError("Failed to create table $tableName\n");
}

try {
    $intType = AE\isColEncrypted() ? SQLSRV_SQLTYPE_INT : null;
    $stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(array(1, null, null, $intType),
          array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UNKNOWN), null)));
    if ($stmt !== false) {
        sqlsrv_free_stmt($stmt);
        die("sqlsrv_query shouldn't have succeeded.");
    }
} catch (Error $err) {
    compareMessages($err);
}

try {
    $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(1, array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_UNKNOWN), null)));
} catch (Error $err) {
    compareMessages($err);
}

restore_error_handler();
$stmt = sqlsrv_query($conn, "INSERT INTO $tableName (c1_int, c2_varchar_max) VALUES (?, ?)", array(array(1, null, null, $intType),
      array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_PHPTYPE_INT), null)));

$errState = 'IMSSP';
$errMessage = 'An invalid PHP type for parameter 2 was specified.';
verifyError(sqlsrv_errors()[0], $errState, $errMessage);
      
echo "Done\n";

sqlsrv_query($conn, "DROP TABLE $tableName");

sqlsrv_close($conn);
 
?>
--EXPECT--
Done