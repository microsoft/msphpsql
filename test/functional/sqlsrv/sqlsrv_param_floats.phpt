--TEST--
Test insertion with floats
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
﻿<?php
require_once('MsCommon.inc');

function execData($withParams)
{
    set_time_limit(0);
    sqlsrv_configure('WarningsReturnAsErrors', 1);

    // connect
    $conn = AE\connect();
    $tableName = 'param_floats';
    $columns = array(new AE\ColumnMeta('float', 'c1_float'),
                     new AE\ColumnMeta('real', 'c2_real'));
    $stmt = AE\createTable($conn, $tableName, $columns);
    if (!$stmt) {
        fatalError("Failed to create table $tableName\n");
    }
    
    if ($withParams) {
        $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_float, c2_real) VALUES (?, ?)", array(array(&$v1, SQLSRV_PARAM_IN), array(&$v2, SQLSRV_PARAM_IN)));
    } else {
        $stmt = sqlsrv_prepare($conn, "INSERT INTO $tableName (c1_float, c2_real) VALUES (?, ?)", array(&$v1, &$v2));
    }

    $values = array();

    $v1 = 1.0;
    array_push($values, $v1);
    $v2 = 2.0;
    array_push($values, $v2);
    sqlsrv_execute($stmt);

    $v1 = 11.0;
    array_push($values, $v1);
    $v2 = 12.0;
    array_push($values, $v2);
    sqlsrv_execute($stmt);

    $v1 = 21.0;
    array_push($values, $v1);
    $v2 = 22.0;
    array_push($values, $v2);
    sqlsrv_execute($stmt);

    $v1 = 31.0;
    array_push($values, $v1);
    $v2 = 32.0;
    array_push($values, $v2);
    sqlsrv_execute($stmt);

    $v1 = 41.0;
    array_push($values, $v1);
    $v2 = 42.0;
    array_push($values, $v2);
    sqlsrv_execute($stmt);

    sqlsrv_free_stmt($stmt);

    $idx = 0;
    $stmt = sqlsrv_query($conn, "SELECT * FROM $tableName");

    $epsilon = 0.00001;

    while ($result = sqlsrv_fetch($stmt)) {
        for ($i = 0; $i < 2; $i++) {
            $value = sqlsrv_get_field($stmt, $i);

            $expected = $values[$idx++];
            $diff = abs(($value - $expected) / $expected);
            if ($diff > $epsilon) {
                echo "Value $value is unexpected\n";
            }
        }
    }
    sqlsrv_free_stmt($stmt);
    
    dropTable($conn, $tableName);
    sqlsrv_close($conn);
}

echo "\nTest begins...\n";

try {
    execData(true);
    execData(false);
} catch (Exception $e) {
    echo $e->getMessage();
}
echo "\nDone\n";

?>
--EXPECT--
﻿
Test begins...

Done
