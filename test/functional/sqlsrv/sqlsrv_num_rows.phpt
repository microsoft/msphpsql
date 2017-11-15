--TEST--
Test sqlsrv_num_rows method.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    $conn = AE\connect();
    $tableName = 'testNumRows';

    $columns = array(new AE\ColumnMeta('int', 'id', 'identity'),
                     new AE\ColumnMeta('nvarchar(100)', 'c1'));
    AE\createTable($conn, $tableName, $columns);
    
    $stmt = AE\insertRow($conn, $tableName, array("c1" => 'TEST'));
    
    // Always Encrypted feature only supports SQLSRV_CURSOR_FORWARD or 
    // SQLSRV_CURSOR_CLIENT_BUFFERED
    if (AE\isColEncrypted()) {
        $options = array('Scrollable' => SQLSRV_CURSOR_CLIENT_BUFFERED);
    } else {
        $options = array('Scrollable' => SQLSRV_CURSOR_KEYSET);
    }
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName", array(), $options);
    $row_nums = sqlsrv_num_rows($stmt);

    echo $row_nums;

    dropTable($conn, 'utf16invalid');

    sqlsrv_free_stmt($stmt);
    sqlsrv_close($conn);
?>

--EXPECT--
1
