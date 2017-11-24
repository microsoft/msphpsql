--TEST--
Fetch missing row
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function missingRowFetch()
{
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // Connect
    $conn = AE\connect();
    $tableName = 'missingRow'; 

    AE\createTestTable($conn, $tableName);

    $stmt = AE\selectFromTable($conn, $tableName);
    $result1 = sqlsrv_fetch($stmt);
    $result2 = sqlsrv_fetch($stmt);

    $errors = sqlsrv_errors(SQLSRV_ERR_ALL);
    $e = $errors[0];
    $value1 = $e['message'];
    print "$value1\n";
    $value2 = $e['code'];
    print "$value2\n";
    $value3 = $e['SQLSTATE'];
    print "$value3\n";

    dropTable($conn, $tableName);
    
    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
}

missingRowFetch();
endTest("sqlsrv_fetch_missing_row");

?>
--EXPECT--
There are no more rows in the active result set.  Since this result set is not scrollable, no more data may be retrieved.
-22
IMSSP
Test "sqlsrv_fetch_missing_row" completed successfully.
