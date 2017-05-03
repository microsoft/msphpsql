--TEST--
Connection Close Test
--DESCRIPTION--
Verifies that a connection can be closed multiple times and
that resources are invalidated when connection is closed.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ConnectionClose()
{
    include 'MsSetup.inc';

    $testName = "Connection - Close";
    StartTest($testName);

    Setup();

    $noRows = 5;
    $conn1 = Connect();
    CreateTable($conn1, $tableName);
    InsertRows($conn1, $tableName, $noRows);

    // Close connection twice
    for ($i = 0; $i < 2; $i++)
    {
        $ret = sqlsrv_close($conn1);
        if ($ret === false)
        {
            die("Unexpected return for sqlsrv_close: $ret");
        }
    }
    
    // Invalid Query
    $stmt1 = sqlsrv_query($conn1, "SELECT * FROM [$tableName]");
    if ($stmt1)
    {
        die("Select query should fail when connection is closed");
    }

    // Invalid Statement
    $conn2 = Connect();
    $stmt2 = SelectFromTable($conn2, $tableName);
    sqlsrv_close($conn2);
    if (sqlsrv_fetch($stmt2))
    {
        die("Fetch should fail when connection is closed");
    }
    
    
    $conn3 = Connect();
    DropTable($conn3, $tableName);  
    sqlsrv_close($conn3);

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
        ConnectionClose();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTREGEX--

Warning: sqlsrv_close\(\): supplied resource is not a valid ss_sqlsrv_conn resource in .*TC24_Close.php on line 21

Warning: sqlsrv_query\(\): supplied resource is not a valid ss_sqlsrv_conn resource in .*TC24_Close.php on line 29

Warning: sqlsrv_fetch\(\): supplied resource is not a valid ss_sqlsrv_stmt resource in .*TC24_Close.php on line 39
Test "Connection - Close" completed successfully.

