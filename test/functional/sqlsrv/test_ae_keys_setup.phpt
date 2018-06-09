--TEST--
Test the existence of Windows Always Encrypted keys generated in the database setup
--DESCRIPTION--
This test iterates through the rows of sys.column_master_keys and/or 
sys.column_encryption_keys to look for the specific column master key and 
column encryption key generated in the database setup
--SKIPIF--
<?php require('skipif_unix.inc'); ?>
--FILE--
<?php
sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

require_once('MsCommon.inc');
$conn = connect();

if (AE\IsQualified($conn)) {
    $query = "SELECT name FROM sys.column_master_keys";
    $stmt = sqlsrv_query($conn, $query);
    $found = false;
    while (sqlsrv_fetch($stmt)) {
        $master_key_name = sqlsrv_get_field($stmt, 0);
        if ($master_key_name == 'AEMasterKey') {
            $found = true;
        }
    }
    // $master_key_name = sqlsrv_get_field($stmt, 0);
    if (!$found) {
        die("Windows Column Master Key not created.\n");
    }

    $query = "SELECT name FROM sys.column_encryption_keys";
    $stmt = sqlsrv_query($conn, $query);
    $found = false;
    while (sqlsrv_fetch($stmt)) {
        $encryption_key_name = sqlsrv_get_field($stmt, 0);
        if ($encryption_key_name == 'AEColumnKey') {
            $found = true;
        }
    }
    if (!$found) {
        die("Windows Column Encryption Key not created.\n");
    }
    sqlsrv_free_stmt($stmt);
}

echo "Test Successfully done.\n";
sqlsrv_close($conn);
?>
--EXPECT--
Test Successfully done.
