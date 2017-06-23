--TEST--
PDO Basic Statement Test
--DESCRIPTION--
Basic verification for PDOStatement.
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

    $testName = "PDO Statement";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val, grp";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), grp VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A', 'Group1'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'B', 'Group2'", null);

    $tsql = "SELECT val, grp FROM [$tableName]";

    // Testing prepared query
    $stmt1 = ExecuteQueryEx($conn1, $tsql, false);
    $stmt1->setFetchMode(PDO::FETCH_NUM);
    foreach ($stmt1 as $data)
    {
        var_dump($data);
    }
    unset($stmt1);

    // Testing direct query
    $stmt1 = $conn1->query($tsql, PDO::FETCH_CLASS, 'Test');
    foreach ($stmt1 as $data)
    {
        var_dump($data);
    }
    unset($stmt1);

    $stmt1 = $conn1->query($tsql, PDO::FETCH_CLASS, 'Test', array('WOW'));
    foreach ($stmt1 as $data)
    {
        var_dump($data);
    }

    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}

class Test
{
    function __construct($name = 'N/A')
    {
        echo __METHOD__ . "($name)\n";
    }
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
--EXPECTF--
array(2) {
  [0]=>
  string(1) "A"
  [1]=>
  string(6) "Group1"
}
array(2) {
  [0]=>
  string(1) "B"
  [1]=>
  string(6) "Group2"
}
Test::__construct(N/A)
object(Test)#%d (2) {
  ["val"]=>
  string(1) "A"
  ["grp"]=>
  string(6) "Group1"
}
Test::__construct(N/A)
object(Test)#%d (2) {
  ["val"]=>
  string(1) "B"
  ["grp"]=>
  string(6) "Group2"
}
Test::__construct(WOW)
object(Test)#%d (2) {
  ["val"]=>
  string(1) "A"
  ["grp"]=>
  string(6) "Group1"
}
Test::__construct(WOW)
object(Test)#%d (2) {
  ["val"]=>
  string(1) "B"
  ["grp"]=>
  string(6) "Group2"
}
Test "PDO Statement" completed successfully.