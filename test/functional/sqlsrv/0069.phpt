--TEST--
Variety of connection parameters.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    function insertTime($conn, $tableName, $datetime2, $datetimeoffset, $time, $useSQLType = false)
    {
        if ($useSQLType) {
            $inputs = array(
              array($datetime2, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATETIME2),
              array($datetimeoffset, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_DATETIMEOFFSET),
              array($time, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_DATETIME, SQLSRV_SQLTYPE_TIME));
        } else {
            $inputs = array($datetime2, $datetimeoffset, $time);
        }

        $insertSql = "INSERT INTO $tableName (c1_datetime2, c2_datetimeoffset, c3_time) VALUES (?,?,?)";
        if (AE\isColEncrypted()) {
            $stmt = sqlsrv_prepare($conn, $insertSql, $inputs);
            if ($stmt) {
                $r = sqlsrv_execute($stmt);
                if (!$r) {
                    fatalError("insertTime: failed to insert a row into $tableName!");
                }
            }
        } else {
            $stmt = sqlsrv_query($conn, $insertSql, $inputs);
        }
        if (!$stmt) {
            fatalError("insertTime: failed to insert a row into $tableName!");
        }
        print_r(sqlsrv_errors(SQLSRV_ERR_WARNINGS));
    }

    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    date_default_timezone_set('America/Vancouver');

    require_once('MsCommon.inc');
    $conn = AE\connect();
    $tableName = 'php_table_SERIL1_1';

    if (AE\isColEncrypted()) {
        // With AE enabled, the sql types and SQLSRV SQLTYPES have to match exactly when binding
        // Since SQLSRV SQLTYPES with datetime columns have no options for precision/scale,
        // Use the default precision
        $columns = array(new AE\ColumnMeta('datetime2', 'c1_datetime2'),
                         new AE\ColumnMeta('datetimeoffset', 'c2_datetimeoffset'),
                         new AE\ColumnMeta('time', 'c3_time'));
        $stmt = AE\createTable($conn, $tableName, $columns);
    } else {
        dropTable($conn, $tableName);
        $stmt = sqlsrv_query($conn, "CREATE TABLE $tableName ([c1_datetime2] datetime2(0), [c2_datetimeoffset] datetimeoffset(0), [c3_time] time(0))");
    }
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }

    // test inserting into date time as a default
    $datetime2 = date_create('1963-02-01 20:56:04.0123456');
    $datetimeoffset = date_create('1963-02-01 20:56:04.0123456 -07:00');
    $time = date_create('20:56:04.98765');

    // Insert two rows with the same values, one with SQL Types one without
    $stmt = insertTime($conn, $tableName, $datetime2, $datetimeoffset, $time);

    $stmt = insertTime($conn, $tableName, $datetime2, $datetimeoffset, $time, true);

    dropTable($conn, $tableName);

    sqlsrv_close($conn);

    echo "test succeeded.";

?>
--EXPECT--
test succeeded.
