--TEST--
PDO Fetch Test with PDO::FETCH_UNIQUE
--DESCRIPTION--
Basic verification for "PDOStatement::fetchAll(PDO::FETCH_UNIQUE)”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function FetchAll()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Fetch All";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val";
    CreateTableEx($conn1, $tableName, "id CHAR(1) NOT NULL PRIMARY KEY, val VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "'A', 'A'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "'B', 'A'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "'C', 'C'", null);

    // Test FetchAll(PDO::FETCH_UNIQUE) without conflict
    $stmt1 = PrepareQuery($conn1, "SELECT id, val FROM [$tableName]");

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_NUM|PDO::FETCH_UNIQUE));

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC|PDO::FETCH_UNIQUE));
    unset($stmt1);


    // Test FetchAll(PDO::FETCH_UNIQUE) with conflict
    $stmt1 = ExecuteQuery($conn1, "SELECT val, id FROM [$tableName]");
    var_dump($stmt1->fetchAll(PDO::FETCH_NUM|PDO::FETCH_UNIQUE));

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
        FetchAll();
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
  ["A"]=>
  array(1) {
    [0]=>
    string(1) "A"
  }
  ["B"]=>
  array(1) {
    [0]=>
    string(1) "A"
  }
  ["C"]=>
  array(1) {
    [0]=>
    string(1) "C"
  }
}
array(3) {
  ["A"]=>
  array(1) {
    ["val"]=>
    string(1) "A"
  }
  ["B"]=>
  array(1) {
    ["val"]=>
    string(1) "A"
  }
  ["C"]=>
  array(1) {
    ["val"]=>
    string(1) "C"
  }
}
array(2) {
  ["A"]=>
  array(1) {
    [0]=>
    string(1) "B"
  }
  ["C"]=>
  array(1) {
    [0]=>
    string(1) "C"
  }
}
Test "PDO Statement - Fetch All" completed successfully.