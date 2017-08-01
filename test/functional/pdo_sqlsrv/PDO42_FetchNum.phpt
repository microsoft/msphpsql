--TEST--
PDO Fetch Test with PDO::FETCH_NUM
--DESCRIPTION--
Basic verification for "PDOStatement::fetchAll(PDO::FETCH_NUM)”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function FetchAll($execMode, $fetchMode)
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Fetch All";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'B'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'C'", null);

    // Query table and retrieve data
    $stmt1 = ExecuteQueryEx($conn1, "SELECT * FROM [$tableName]", $execMode);
    var_dump($stmt1->fetchAll($fetchMode));

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
        FetchAll(true, PDO::FETCH_NUM);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
array(3) {
  [0]=>
  array(2) {
    [0]=>
    string(1) "1"
    [1]=>
    string(1) "A"
  }
  [1]=>
  array(2) {
    [0]=>
    string(1) "2"
    [1]=>
    string(1) "B"
  }
  [2]=>
  array(2) {
    [0]=>
    string(1) "3"
    [1]=>
    string(1) "C"
  }
}
Test "PDO Statement - Fetch All" completed successfully.