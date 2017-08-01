--TEST--
Invalid Query Test
--DESCRIPTION--
Verifies of "sqlsrv_query" response to invalid query attempts 
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function InvalidQuery()
{
    include 'MsSetup.inc';

    $testName = "Statement - Invalid Query";
    StartTest($testName);

    Setup();
    $conn1 = Connect();
    
    // Invalid Query
    $stmt1 = sqlsrv_query($conn1, "INVALID QUERY");
    if ($stmt1)
    {
        die("Invalid query should have failed.");
    }

    $dataType = "[c1] int, [c2] int";
    CreateTableEx($conn1, $tableName, $dataType);

    // Invalid PHPTYPE parameter
    $stmt2 = sqlsrv_query($conn1, "INSERT INTO [$tableName] (c1, c2) VALUES (?, ?)",
                  array(1, array(2, SQLSRV_PARAM_IN, 'SQLSRV_PHPTYPE_UNKNOWN')));
    if ($stmt2)
    {
        die("Insert query with invalid parameter should have failed.");
    }

    // Invalid option
    $stmt3 = sqlsrv_query($conn1, "INSERT INTO [$tableName] (c1, c2) VALUES (?, ?)", array(1, 2),
                  array('doSomething' => 1));
    if ($stmt3)
    {
        die("Insert query with invalid option should have failed.");
    }

    // Invalid select
    DropTable($conn1, $tableName);
    $stmt4 = sqlsrv_query($conn1, "SELECT * FROM [$tableName]");
    if ($stmt4)
    {
        die("Select query should have failed.");
    }
    
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
        InvalidQuery();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}


Repro();

?>
--EXPECT--
Test "Statement - Invalid Query" completed successfully.

