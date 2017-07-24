--TEST--
retrieval of names of column master key and column encryption key generated in the database setup
--SKIPIF--
<?php if (strtoupper(substr(PHP_OS, 0, 3)) !== 'WIN') die("Skipped: Test for Windows"); ?>
--FILE--
<?php
sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

require( 'MsCommon.inc' );
$conn = Connect();
    
if (IsAEQualified($conn)){
    $verify_cert = shell_exec('certutil -user -verifyStore My 237F94738E7F5214D8588006C2269DBC6B370816');
    $cert_exists = false;
    if (strpos($verify_cert, 'successfully') != false)
    {
        $cert_exists = true;
    }
    else {
        die("Certificate does not exist.\n";
    }
   
    $query = "SELECT name FROM sys.column_master_keys";
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_fetch($stmt);
    $master_key_name = sqlsrv_get_field($stmt, 0);
   
    $query = "SELECT name FROM sys.column_encryption_keys";
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_fetch($stmt);
    $encryption_key_name = sqlsrv_get_field($stmt, 0);
    
    if ($cert_exists && $master_key_name == 'AEMasterKey' && $encryption_key_name == 'AEColumnKey'){
        echo "Test Successfully.\n";
    }
    else {
        die("Column Master Key and Column Encryption Key not created.\n");
    }
    sqlsrv_free_stmt($stmt);
}
else {
    echo "Test Successfully.\n";
}
sqlsrv_close( $conn );
?>
--EXPECT--
Test Successfully.