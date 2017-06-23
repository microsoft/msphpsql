--TEST--
PDO Row Count Test
--DESCRIPTION--
Verification for "PDOStatement::rowCount()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function RowCountTest()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Row Count";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, label";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, label CHAR(1)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'a'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'b'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'c'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "4, 'd'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "5, 'e'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "6, 'f'", null);

    // Check row count
    $tsql1 = "SELECT id FROM [$tableName] WHERE 1 = 0";
    //CheckRowCount($conn1, $tsql1, 0);

    $tsql2 = "SELECT id FROM [$tableName] WHERE id = 1";
    CheckRowCount($conn1, $tsql2, -1);

    $tsql3 = "INSERT INTO [$tableName] ($dataCols) VALUES (7, 'g')";
    CheckRowCount($conn1, $tsql3, 1);

    $tsql3 = "DELETE FROM [$tableName]";
    CheckRowCount($conn1, $tsql3, 7);

    // Cleanup
    DropTable($conn1, $tableName);
    $conn1 = null;

    EndTest($testName);
}

function CheckRowCount($conn, $tsql, $rows)
{
    $stmt = ExecuteQuery($conn, $tsql);
    $count = $stmt->rowCount();
    if ($count !== $rows)
    {
        printf("Unexpected row count: $count instead of $rows\n");
    }
    unset($stmt);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        RowCountTest();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDO Statement - Row Count" completed successfully.