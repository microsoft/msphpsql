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

// When testing with PHP 8.0 it throws a TypeError instead of a warning. Thus implement a custom 
// warning handler such that with PHP 7.x the warning would be handled to throw a TypeError.
function warningHandler($errno, $errstr) 
{ 
    throw new TypeError($errstr);
}

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
    try {
        if (sqlsrv_num_fields($stmt1) === false) {
            handleErrors();
        } else {
            die("A closed statement cannot be reused.");
        }
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    trace("\nClosing the statement again (no error expected) ...\n");
    try {
        if (sqlsrv_free_stmt($stmt1) === false) {
            fatalError("A statement can be closed multiple times.");
        }
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }
    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

try {
    set_error_handler("warningHandler", E_WARNING);
    close();
} catch (Exception $e) {
    echo $e->getMessage() . PHP_EOL;
}

?>
--EXPECT--
sqlsrv_num_fields(): supplied resource is not a valid ss_sqlsrv_stmt resource
sqlsrv_free_stmt(): supplied resource is not a valid ss_sqlsrv_stmt resource
Test "Statement - Close" completed successfully.
