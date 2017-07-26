--TEST--
retrieval of names of column master key and column encryption key generated in the database setup
--SKIPIF--
<?php require('skipif_unix.inc'); ?>
--FILE--
<?php
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

require( 'MsCommon.inc' );
$conn = Connect();
    
if (IsAEQualified($conn)){
    $query = "SELECT name FROM sys.column_master_keys";
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_fetch($stmt);
    $master_key_name = sqlsrv_get_field($stmt, 0);
   
    $query = "SELECT name FROM sys.column_encryption_keys";
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_fetch($stmt);
    $encryption_key_name = sqlsrv_get_field($stmt, 0);
    
    if ($master_key_name == 'AEMasterKey' && $encryption_key_name == 'AEColumnKey'){
        echo "Test Successfully done.\n";
    }
    else {
        echo "Column Master Key and Column Encryption Key not created.\n";
    }
    sqlsrv_free_stmt($stmt);
}
else {
    echo "Test Successfully done.\n";
}
sqlsrv_close( $conn );
?>
--EXPECT--
Test Successfully done.