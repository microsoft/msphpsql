--TEST--
PDO Exec Test
--DESCRIPTION--
Basic verification for PDO::exec().
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

    $testName = "PDO Connection - Exec";
    StartTest($testName);

    $conn1 = Connect();

    $dbName1 = $databaseName."1";
    $dbName2 = $databaseName."2";
    CreateDB($conn1, $dbName1);
    DoExec(1, $conn1, "ALTER DATABASE [$dbName1] MODIFY NAME = [$dbName2]", 0);
    DoExec(2, $conn1, "DROP DATABASE [$dbName2]", 0);

    CreateTableEx($conn1, $tableName, "id INT, val CHAR(10)", null);
    DoExec(3, $conn1, "CREATE INDEX [$tableIndex] ON [$tableName](id)", 0);
    DoExec(4, $conn1, "DROP INDEX [$tableIndex] ON [$tableName]", 0);
    DoExec(5, $conn1, "ALTER TABLE [$tableName] DROP COLUMN id", 0);
    DoExec(6, $conn1, "ALTER TABLE [$tableName] ADD id INT", 0);
    DoExec(7, $conn1, "INSERT INTO [$tableName] (id, val) VALUES (1, 'ABC')", 1);

    // Cleanup
    DropTable($conn1, $tableName);
    DropDB($conn1, $dbName1);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}


function DoExec($offset, &$conn, $tsql, $expected)
{
    $ret = false;
    try
    {
        $actual = @$conn->exec($tsql);
        if ($actual === $expected)
        {
            $ret = true;
        }
        else
        {
            printf("[%03d] Expecting '%s' (%s) instead of '%s' (%s) when executing '%s', [%s] %s\n",
                $offset, $expected, gettype($expected), $actual, gettype($actual),
                $tsql, $conn->errorCode(), implode(' ', $conn->errorInfo()));
        }
    }
    catch (PDOException $e)
    {
        printf("[%03d] Execution of '%s' has failed, [%s] %s\n",
            $offset, $tsql, $conn->errorCode(), implode(' ', $conn->errorInfo()));
    }
    return ($ret);
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
--EXPECT--
Test "PDO Connection - Exec" completed successfully.