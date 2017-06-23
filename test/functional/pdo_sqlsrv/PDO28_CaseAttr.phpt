--TEST--
PDO Test for PDO::ATTR_CASE
--DESCRIPTION--
Verification of fetch behavior when using PDO::ATTR_CASE.
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

    $testName = "PDO Connection - Case Attribute";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val";
    CreateTableEx($conn1, $tableName, "ID int NOT NULL PRIMARY KEY, val VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'B'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'C'", null);

    // Retrieve data as array with no change on columns case
    $conn1->setAttribute(PDO::ATTR_CASE, PDO::CASE_NATURAL);
    $stmt1 = PrepareQuery($conn1, "SELECT * FROM [$tableName]");
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC));
    $stmt1->closeCursor();

    // Retrieve data as array with lower case columns
    $conn1->setAttribute(PDO::ATTR_CASE, PDO::CASE_LOWER);
    $stmt1 = PrepareQuery($conn1, "SELECT * FROM [$tableName]");
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC));
    $stmt1->closeCursor();

    // Retrieve data as array with upper case columns
    $conn1->setAttribute(PDO::ATTR_CASE, PDO::CASE_UPPER);
    $stmt1 = PrepareQuery($conn1, "SELECT * FROM [$tableName]");
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC));
    $stmt1->closeCursor();

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
--EXPECT--
array(3) {
  [0]=>
  array(2) {
    ["ID"]=>
    string(1) "1"
    ["val"]=>
    string(1) "A"
  }
  [1]=>
  array(2) {
    ["ID"]=>
    string(1) "2"
    ["val"]=>
    string(1) "B"
  }
  [2]=>
  array(2) {
    ["ID"]=>
    string(1) "3"
    ["val"]=>
    string(1) "C"
  }
}
array(3) {
  [0]=>
  array(2) {
    ["id"]=>
    string(1) "1"
    ["val"]=>
    string(1) "A"
  }
  [1]=>
  array(2) {
    ["id"]=>
    string(1) "2"
    ["val"]=>
    string(1) "B"
  }
  [2]=>
  array(2) {
    ["id"]=>
    string(1) "3"
    ["val"]=>
    string(1) "C"
  }
}
array(3) {
  [0]=>
  array(2) {
    ["ID"]=>
    string(1) "1"
    ["VAL"]=>
    string(1) "A"
  }
  [1]=>
  array(2) {
    ["ID"]=>
    string(1) "2"
    ["VAL"]=>
    string(1) "B"
  }
  [2]=>
  array(2) {
    ["ID"]=>
    string(1) "3"
    ["VAL"]=>
    string(1) "C"
  }
}
Test "PDO Connection - Case Attribute" completed successfully.