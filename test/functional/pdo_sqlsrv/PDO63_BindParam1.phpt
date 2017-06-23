--TEST--
PDO Bind Param Test
--DESCRIPTION--
Verification for "PDOStatement::bindParam()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Bind()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Bind Param";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, name";
    CreateTableEx($conn1, $tableName, "id int, name VARCHAR(20)", null);
    $conn1->exec( "CREATE CLUSTERED INDEX [idx_test_int] ON " . $tableName . "(id)" );


    // Insert test data
    $stmt1 = PrepareQuery($conn1, "INSERT INTO [$tableName] ($dataCols) VALUES(0, :name)");
    $name = NULL;
    $before_bind = $name;
    $stmt1->bindParam(':name', $name);

    // Check that bindParam does not modify parameter
    if ($name !== $before_bind)
    {
        echo "bind: fail\n";
    }
    else
    {
        echo "bind: success\n";
    }

    var_dump($stmt1->execute());
    unset($stmt1);

    // Retrieve test data
    $stmt1 = ExecuteQuery($conn1, "SELECT name FROM [$tableName] WHERE id = 0");
    var_dump($stmt1->fetchColumn());


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
        Bind();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
bind: success
bool(true)
NULL
Test "PDO Statement - Bind Param" completed successfully.