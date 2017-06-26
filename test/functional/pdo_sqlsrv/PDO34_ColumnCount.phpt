--TEST--
PDO Columns Count Test
--DESCRIPTION--
Basic verification for "PDOStatement::columnCount()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ExecStmt()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Column Count";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val, val2";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), val2 VARCHAR(16)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A', 'A'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'B', 'B'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'C', 'C'", null);

    $tsql1 = "SELECT id, val       FROM [$tableName]";
    $tsql2 = "SELECT id, val, val2 FROM [$tableName]";
    $tsql3 = "SELECT COUNT(*)      FROM [$tableName]";

    // Testing with direct query
    foreach (array($tsql1, $tsql2, $tsql3) as $tsql)
    {
        $stmt1 = ExecuteQuery($conn1, $tsql);
        $res = $stmt1->columnCount();
            echo "Counted $res columns.\n";
        unset($stmt1);
    }

    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $conn1 = null;

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
        ExecStmt();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTF--
Counted 2 columns.
Counted 3 columns.
Counted 1 columns.
Test "PDO Statement - Column Count" completed successfully.