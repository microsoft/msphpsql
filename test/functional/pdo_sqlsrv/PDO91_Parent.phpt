--TEST--
PDO Get Parent Class Test
--DESCRIPTION--
Verification for "get_parent_class()" functionality.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function ExecTest()
{
    include 'MsSetup.inc';

    $testName = "PDO - Get Parent";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    CreateTableEx($conn1, $tableName, "id int", null);
    $conn1->exec( "CREATE CLUSTERED INDEX [idx_test_int] ON " . $tableName . "(id)" );
    InsertRowEx($conn1, $tableName, "id", "23", null);

    // Retrieve data
    $stmt1 = PrepareQuery($conn1, "SELECT id FROM [$tableName]");
    $stmt1->execute();
    $result = $stmt1->fetch(PDO::FETCH_LAZY);

    // Retrieve class and parent
    echo get_class($result), "\n";
    var_dump(get_parent_class($result));

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
        ExecTest();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
PDORow
bool(false)
Test "PDO - Get Parent" completed successfully.