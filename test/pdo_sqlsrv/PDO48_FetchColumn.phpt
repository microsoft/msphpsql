--TEST--
PDO Fetch Test with PDO::FETCH_COLUMN
--DESCRIPTION--
Verification for "PDOStatement::fetchAll(PDO::FETCH_COLUMN)".
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

    $testName = "PDO Statement - Fetch All";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val, val2";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), val2 VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A', 'A2'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'A', 'B2'", null);

    // Testing with prepared query
    $stmt1 = PrepareQuery($conn1, "SELECT id, val, val2 FROM [$tableName]");

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_COLUMN));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_COLUMN, 2));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE, 0));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE, 1));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE, 2));
    unset($stmt1);

    $stmt1 = PrepareQuery($conn1, "SELECT val, val2 FROM [$tableName]");
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_GROUP));

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
array(2) {
  [0]=>
  string(1) "1"
  [1]=>
  string(1) "2"
}
array(2) {
  [0]=>
  string(2) "A2"
  [1]=>
  string(2) "B2"
}
array(2) {
  [1]=>
  array(1) {
    [0]=>
    string(1) "A"
  }
  [2]=>
  array(1) {
    [0]=>
    string(1) "A"
  }
}
array(2) {
  [1]=>
  string(1) "A"
  [2]=>
  string(1) "A"
}
array(2) {
  [1]=>
  string(1) "1"
  [2]=>
  string(1) "2"
}
array(2) {
  [1]=>
  string(1) "A"
  [2]=>
  string(1) "A"
}
array(2) {
  [1]=>
  string(2) "A2"
  [2]=>
  string(2) "B2"
}
array(1) {
  ["A"]=>
  array(2) {
    [0]=>
    string(2) "A2"
    [1]=>
    string(2) "B2"
  }
}
Test "PDO Statement - Fetch All" completed successfully.