--TEST--
Statement Close Test
--DESCRIPTION--
Verifies that a statement can be closed more than once without
triggering an error condition.
Validates that a closed statement cannot be reused.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function close()
{
    $testName = "Statement - Close";
    startTest($testName);

    setup();
    $conn1 = AE\connect();
    $tableName = 'TC36test';
    
    AE\createTestTable($conn1, $tableName);

    trace("Executing SELECT query on $tableName ...");
    $stmt1 = AE\selectFromTable($conn1, $tableName);
    trace(" successfull.\n");
    sqlsrv_free_stmt($stmt1);

    trace("Attempting to retrieve the number of fields after statement was closed ...\n");
    if (sqlsrv_num_fields($stmt1) === false) {
        handleErrors();
    } else {
        die("A closed statement cannot be reused.");
    }

    trace("\nClosing the statement again (no error expected) ...\n");

    if (sqlsrv_free_stmt($stmt1) === false) {
        fatalError("A statement can be closed multiple times.");
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

try {
    close();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECTREGEX--

Warning: sqlsrv_num_fields\(\): supplied resource is not a valid ss_sqlsrv_stmt resource in .*TC36_Close.php on line 21

Warning: sqlsrv_free_stmt\(\): supplied resource is not a valid ss_sqlsrv_stmt resource in .*TC36_Close.php on line 29
Test "Statement - Close" completed successfully.
