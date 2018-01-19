--TEST--
scrollable results with no rows.
--DESCRIPTION--
this test is very similar to test_scrollable.phpt... might consider removing this test as a duplicate
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');

    $conn = AE\connect();
    $tableName = 'ScrollTest';

    $columns = array(new AE\ColumnMeta('int', 'id'),
                     new AE\ColumnMeta('char(10)', 'value'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table for the test]n");
    }
    sqlsrv_free_stmt($stmt);
    
    $query = "SELECT * FROM $tableName";
    $options = array('Scrollable' => 'static');

    $stmt = sqlsrv_query($conn, $query, array(), $options);
    $rows = sqlsrv_has_rows($stmt);
    if ($rows != false) {
        fatalError("Should be no rows present");
    };

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $row = sqlsrv_fetch_array($stmt);
    print_r($row);
    if ($row === false) {
        print_r(sqlsrv_errors(), true);
    }
    
    $stmt = sqlsrv_query($conn, $query);
    $rows = sqlsrv_has_rows($stmt);
    if ($rows != false) {
        fatalError("Should be no rows present");
    };

    if ($stmt === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $row = sqlsrv_fetch_array($stmt);
    print_r($row);
    if ($row === false) {
        print_r(sqlsrv_errors(), true);
    }

    dropTable($conn, $tableName);
    echo "Test succeeded.\n";

?>
--EXPECT--
Test succeeded.
