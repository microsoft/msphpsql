--TEST--
PDO Query Test with PDO::FETCH_LAZY 
--DESCRIPTION--
Verification for "PDO::query() with PDO::FETCH_LAZY mode".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Fetch()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Fetch Lazy";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, name";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, name VARCHAR(20)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'test1'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'test2'", null);

    // Testing with direct query
    $stmt1 = $conn1->query("SELECT * FROM [$tableName]", PDO::FETCH_LAZY);
    foreach ($stmt1 as $v)
    {
        echo "lazy: " . $v->id.$v->name."\n";
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
        Fetch();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
lazy: 1test1
lazy: 2test2
Test "PDO Statement - Fetch Lazy" completed successfully.