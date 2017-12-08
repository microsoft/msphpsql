--TEST--
fix for 182741.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = AE\connect();
    $tableName = 'test_182741';
    $columns = array(new AE\ColumnMeta('int', 'int_type'),
                     new AE\ColumnMeta('text', 'text_type'),
                     new AE\ColumnMeta('ntext', 'ntext_type'), 
                     new AE\ColumnMeta('image', 'image_type'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    $sql = "INSERT INTO $tableName ([int_type], [text_type], [ntext_type], [image_type]) VALUES(?, ?, ?, ?)";
    $params = array(1, 
                    array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR)),
                    array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR)),
                    array("Test Data", SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)));
    $stmt = AE\executeQueryParams($conn, $sql, $params);
    
    dropTable($conn, $tableName);

    sqlsrv_free_stmt($stmt);

    sqlsrv_close($conn);

    echo "Test succeeded.";
?>
--EXPECT--
Test succeeded.
