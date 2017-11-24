--TEST--
Data Roundtrip via Stored Proc
--DESCRIPTION--
Verifies that data is not corrupted through a roundtrip via a store procedure.
Checks all character data types.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function bugRepro()
{
    $testName = "Regression VSTS 846501";
    startTest($testName);

    setup();
    $conn1 = AE\connect();
    // empty parameter array
    $tableName = "test_bq";
    $columns = array(new AE\ColumnMeta("int", "id", "IDENTITY NOT NULL"),
                     new AE\ColumnMeta("varchar(max)", "test_varchar_max"));
    $s = AE\createTable($conn1, $tableName, $columns);
    if ($s === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $s = sqlsrv_query($conn1, "CREATE CLUSTERED INDEX [idx_test_int] ON $tableName (id)");
    if ($s === false) {
        die(print_r(sqlsrv_errors(), true));
    }
    $inputs = array("test_varchar_max" => "ABCD"); 
    $s = AE\insertRow($conn1, $tableName, $inputs);
    if ($s === false) {
        die(print_r(sqlsrv_errors(), true));
    }

    $tsql = "select test_varchar_max from $tableName";
    $result = AE\executeQueryEx($conn1, $tsql, array( "Scrollable" => SQLSRV_CURSOR_CLIENT_BUFFERED ));

    while ($row = sqlsrv_fetch_array($result, SQLSRV_FETCH_BOTH)) {
        print $row['test_varchar_max']."\n";
    }

    dropTable($conn1, $tableName);

    endTest($testName);
}

try {
    bugRepro();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
ABCD
Test "Regression VSTS 846501" completed successfully.
