--TEST--
Statement Cancel Test
--DESCRIPTION--
Verifies that “sqlsrv_cancel” discards any pending data in current result set
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Cancel()
{
    include 'MsSetup.inc';

    $testName = "Statement - Cancel";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    CreateTable($conn1, $tableName);
    InsertRows($conn1, $tableName, 5);  

    Trace("Executing SELECT query on $tableName ...");
    $stmt1 = SelectFromTable($conn1, $tableName);
    if (sqlsrv_fetch($stmt1) === false)
    {
        FatalError("Failed to retrieve data from test table");
    }
    Trace(" data fetched successfully.\n");

    Trace("Cancel statement and attempt another fetch (expected to fail) ...\n");
    sqlsrv_cancel($stmt1);
    if (sqlsrv_fetch($stmt1) === false)
    {
        handle_errors();
    }
    else
    {
        die("No succesfull data fetch expectd after statement cancel");
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
        Cancel();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "Statement - Cancel" completed successfully.
