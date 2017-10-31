--TEST--
Invalid Query Test
--DESCRIPTION--
Verifies of "sqlsrv_query" response to invalid query attempts
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function invalidQuery()
{
    $testName = "Statement - Invalid Query";
    startTest($testName);

    setup();
    $conn1 = AE\connect();

    // Invalid Query
    $stmt1 = sqlsrv_query($conn1, "INVALID QUERY");
    if ($stmt1) {
        die("Invalid query should have failed.");
    }

    $tableName = 'TC38test';
    $columns = array(new AE\ColumnMeta('int', 'c1'), 
                     new AE\ColumnMeta('int', 'c2'));
    AE\createTable($conn1, $tableName, $columns);
    
    // Invalid PHPTYPE parameter
    $stmt2 = sqlsrv_query(
        $conn1,
        "INSERT INTO [$tableName] (c1, c2) VALUES (?, ?)",
        array(1, array(2, SQLSRV_PARAM_IN, 'SQLSRV_PHPTYPE_UNKNOWN'))
    );
    if ($stmt2) {
        die("Insert query with invalid parameter should have failed.");
    }

    // Invalid option
    $stmt3 = sqlsrv_query(
        $conn1,
        "INSERT INTO [$tableName] (c1, c2) VALUES (?, ?)", array(1, 2), array('doSomething' => 1)
    );
    if ($stmt3) {
        die("Insert query with invalid option should have failed.");
    }

    // Invalid select
    dropTable($conn1, $tableName);
    $stmt4 = sqlsrv_query($conn1, "SELECT * FROM [$tableName]");
    if ($stmt4) {
        die("Select query should have failed.");
    }
    sqlsrv_close($conn1);

    endTest($testName);
}

try {
    invalidQuery();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Statement - Invalid Query" completed successfully.
