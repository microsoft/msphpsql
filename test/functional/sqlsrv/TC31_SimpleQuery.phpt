--TEST--
Simple Query Test
--DESCRIPTION--
Basic verification of query statements (via "sqlsrv_query"):
- Establish a connection
- Creates a table (including all 28 SQL types currently supported)
- Executes a SELECT query (on the empty table)
- Verifies the outcome
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

require_once('MsCommon.inc');

function SimpleQuery()
{
    $testName = "Statement - Simple Query";
    startTest($testName);

    setup();
    $tableName = 'TC31test';

    $conn1 = connect();

    createTable($conn1, $tableName);

    trace("Executing SELECT query on $tableName ...");
    $stmt1 = selectFromTable($conn1, $tableName);
    $rows = rowCount($stmt1);
    ;
    sqlsrv_free_stmt($stmt1);
    trace(" $rows rows retrieved.\n");

    if ($rows > 0) {
        die("Table $tableName, expected to be empty, has $rows rows.");
    }

    dropTable($conn1, $tableName);

    sqlsrv_close($conn1);

    endTest($testName);
}

//--------------------------------------------------------------------
// repro
//
//--------------------------------------------------------------------
function repro()
{
    try {
        SimpleQuery();
    } catch (Exception $e) {
        echo $e->getMessage();
    }
}


repro();

?>
--EXPECT--
Test "Statement - Simple Query" completed successfully.
