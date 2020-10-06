--TEST--
Connection Close Test
--DESCRIPTION--
Verifies that a connection can be closed multiple times and
that resources are invalidated when connection is closed.
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

function connectionClose()
{
    $testName = "Connection - Close";
    startTest($testName);
    setup();
    $conn1 = AE\connect();
    $tableName = 'TC24test';  
    
    // Insert some random rows
    AE\createTestTable($conn1, $tableName);
    AE\insertTestRows($conn1, $tableName, 5);

    try {
        // Close connection twice
        for ($i = 0; $i < 2; $i++) {
            $ret = sqlsrv_close($conn1);
            if ($ret === false) {
                die("Unexpected return for sqlsrv_close: $ret");
            }
        }
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    try {
        // Invalid Query
        $stmt1 = sqlsrv_query($conn1, "SELECT * FROM [$tableName]");
        if ($stmt1) {
            die("Select query should fail when connection is closed");
        }
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    try {
        // Invalid Statement
        $conn2 = AE\connect();
        $stmt2 = AE\selectFromTable($conn2, $tableName);
        sqlsrv_close($conn2);
        if (sqlsrv_fetch($stmt2)) {
            die("Fetch should fail when connection is closed");
        }
    } catch (TypeError $e) {
        echo $e->getMessage() . PHP_EOL;
    }

    $conn3 = AE\connect();
    dropTable($conn3, $tableName); 
    sqlsrv_close($conn3);    

    endTest($testName);
}

try {
    set_error_handler("warningHandler", E_WARNING);
    connectionClose();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
sqlsrv_close(): supplied resource is not a valid ss_sqlsrv_conn resource
sqlsrv_query(): supplied resource is not a valid ss_sqlsrv_conn resource
sqlsrv_fetch(): supplied resource is not a valid ss_sqlsrv_stmt resource
Test "Connection - Close" completed successfully.
