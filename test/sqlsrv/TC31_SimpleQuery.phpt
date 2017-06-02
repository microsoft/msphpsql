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

include 'MsCommon.inc';

function SimpleQuery()
{
    include 'MsSetup.inc';

    $testName = "Statement - Simple Query";
    StartTest($testName);

    Setup();
    $conn1 = Connect();

    CreateTable($conn1, $tableName);

    Trace("Executing SELECT query on $tableName ...");  
    $stmt1 = SelectFromTable($conn1, $tableName);
    $rows = RowCount($stmt1);;
    sqlsrv_free_stmt($stmt1);
    Trace(" $rows rows retrieved.\n");

    if ($rows > 0)
    {
        die("Table $tableName, expected to be empty, has $rows rows.");
    }

    DropTable($conn1, $tableName);  
    
    sqlsrv_close($conn1);

    EndTest($testName);
}

//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{
    try
    {
        SimpleQuery();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}


Repro();

?>
--EXPECT--
Test "Statement - Simple Query" completed successfully.

