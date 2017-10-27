--TEST--
Query Timeout Test
--DESCRIPTION--
Verifies the functionality of QueryTimeout option for both "sqlsrv_query"
and "sqlsrv_prepare".
Executes a batch query that is expected to time out because it includes
a request to delay the server execution (via WAITFOR DELAY) for a duration
longer than the query timeout.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

function queryTimeout()
{
    $testName = "Statement - Query Timeout";
    startTest($testName);

    setup();
    $conn1 = AE\connect();
    $tableName = 'TC37test';
    AE\createTestTable($conn1, $tableName);

    trace("Executing batch queries requiring 3 seconds with 1 second timeout.\n");
    $query = "WAITFOR DELAY '00:00:03'; SELECT * FROM [$tableName]";
    $option = array('QueryTimeout' => 1);

    // Test timeout with sqlsrv_query()
    trace("\tDirect execution ...");
    $stmt1 = sqlsrv_query($conn1, $query, null, $option);
    if ($stmt1 === false) {
        trace(" query timed out (as expected).\n");
    } else {
        die("Query was expected to time out");
    }

    // Test timeout with sqlsrv_prepare()/sqlsrv_execute()
    trace("\tPrepared execution ...");
    $stmt2 = sqlsrv_prepare($conn1, $query, null, $option);
    if ($stmt2 === false) {
        fatalError("Query preparation failed: $query");
    }
    $execOutcome = sqlsrv_execute($stmt2);
    if ($execOutcome === false) {
        trace(" query timed out (as expected).\n");
    } else {
        die("Query execution was expected to time out");
    }
    sqlsrv_free_stmt($stmt2);

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

try {
    queryTimeout();
} catch (Exception $e) {
    echo $e->getMessage();
}

?>
--EXPECT--
Test "Statement - Query Timeout" completed successfully.
