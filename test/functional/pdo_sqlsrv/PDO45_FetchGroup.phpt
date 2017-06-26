--TEST--
PDO Fetch Test with PDO::FETCH_GROUP
--DESCRIPTION--
Basic verification for "PDOStatement::fetchAll(PDO::FETCH_GROUP)”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function FetchAll($fetchMode)
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Fetch All";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'A'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'C'", null);

    // Query table and retrieve data
    $stmt1 = PrepareQuery($conn1, "SELECT val, id FROM [$tableName]");

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_NUM|$fetchMode));

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC|$fetchMode));

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
        FetchAll(PDO::FETCH_GROUP);
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
array(2) {
  ["A"]=>
  array(2) {
    [0]=>
    array(1) {
      [0]=>
      string(1) "1"
    }
    [1]=>
    array(1) {
      [0]=>
      string(1) "2"
    }
  }
  ["C"]=>
  array(1) {
    [0]=>
    array(1) {
      [0]=>
      string(1) "3"
    }
  }
}
array(2) {
  ["A"]=>
  array(2) {
    [0]=>
    array(1) {
      ["id"]=>
      string(1) "1"
    }
    [1]=>
    array(1) {
      ["id"]=>
      string(1) "2"
    }
  }
  ["C"]=>
  array(1) {
    [0]=>
    array(1) {
      ["id"]=>
      string(1) "3"
    }
  }
}
Test "PDO Statement - Fetch All" completed successfully.