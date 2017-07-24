--TEST--
retrieval of names of column master key and column encryption key generated in the database setup
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
    sqlsrv_configure( 'WarningsReturnAsErrors', 0 );
    sqlsrv_configure( 'LogSeverity', SQLSRV_LOG_SEVERITY_ALL );

    require( 'MsCommon.inc' );

    $conn = Connect();
   
    $query = "SELECT name FROM sys.column_master_keys";
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_fetch($stmt);
    $master_key_name = sqlsrv_get_field($stmt, 0);
   
    $query = "SELECT name FROM sys.column_encryption_keys";
    $stmt = sqlsrv_query($conn, $query);
    sqlsrv_fetch($stmt);
    $encryption_key_name = sqlsrv_get_field($stmt, 0);
    
    echo "Column Master Key generated: $master_key_name \n";
    echo "Column Encryption Key generated: $encryption_key_name \n";

    sqlsrv_free_stmt($stmt);
    sqlsrv_close( $conn );
?>
--EXPECT--
Column Master Key generated: AEMasterKey
Column Encryption Key generated: AEColumnKey