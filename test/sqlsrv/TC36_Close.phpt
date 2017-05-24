--TEST--
Statement Close Test
--DESCRIPTION--
Verifies that a statement can be closed more than once without
triggering an error condition.
Validates that a closed statement cannot be reused.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Close()
{
    include 'MsSetup.inc';

    $testName = "Statement - Close";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    CreateTable($conn1, $tableName);

    Trace("Executing SELECT query on $tableName ...");
    $stmt1 = SelectFromTable($conn1, $tableName);
    Trace(" successfull.\n");
    sqlsrv_free_stmt($stmt1);

    Trace("Attempting to retrieve the number of fields after statement was closed ...\n");
    if (sqlsrv_num_fields($stmt1) === false)
    {
        handle_errors();
    }
    else
    {
        die("A closed statement cannot be reused.");
    }

    Trace("\nClosing the statement again (no error expected) ...\n");
    
    if (sqlsrv_free_stmt($stmt1) === false)
    {
        FatalError("A statement can be closed multiple times.");
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
        Close();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTREGEX--

Warning: sqlsrv_num_fields\(\): supplied resource is not a valid ss_sqlsrv_stmt resource in .*TC36_Close.php on line 21

Warning: sqlsrv_free_stmt\(\): supplied resource is not a valid ss_sqlsrv_stmt resource in .*TC36_Close.php on line 32
Test "Statement - Close" completed successfully.


