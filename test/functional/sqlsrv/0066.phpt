--TEST--
inserting and retrieving UTF-8 text.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
    sqlsrv_configure('WarningsReturnAsErrors', 0);
    sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

    require_once('MsCommon.inc');
    $c = AE\connect();
  
    // test a varchar, nvarchar non max, and nvarchar max
    $columns = array(new AE\ColumnMeta('varchar(100)', 'c1'),
                     new AE\ColumnMeta('nvarchar(100)', 'c2'),
                     new AE\ColumnMeta('nvarchar(max)', 'c3'));
    $stmt = AE\createTable($c, "utf8test", $columns);
    if (!$stmt) {
        fatalError("Failed to create table 'utf8test'\n");
    }

    $utf8 = pack('H*', 'efbbbfc5a6c4a5c4afc59d20c790c59f20e1baa120c5a5c499c5a1c5a720e1bb97c69220c399c5a4e282a32d3820c2a2d19be1baa7c599e1bab1c2a2c5a3e1bb81c59520c48fc78ec5a5e1baad');

    $params = array(array(&$utf8, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8')),
                    array(&$utf8, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8')),
                    array(&$utf8, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING('utf-8')));
    $insertSql = "INSERT INTO utf8test (c1, c2, c3) VALUES (?,?,?)";
    $s = AE\executeQueryParams($c, $insertSql, $params);

    $s = AE\executeQuery($c, 'SELECT * FROM utf8test');
    if ($s === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    if (sqlsrv_fetch($s) === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $u = sqlsrv_get_field($s, 1, SQLSRV_PHPTYPE_STREAM('utf-8'));
    if ($u === false) {
        die(print_r(sqlsrv_errors(), true));
    } else {
        $utf8_2 = fread($u, 10000);
        if ($utf8 != $utf8_2) {
            fatalError("round trip failed");
        }
    }
    dropTable($c, 'utf8test');

    echo "Test succeeded\n";

?>
--EXPECT--
Test succeeded
