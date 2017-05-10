--TEST--
PDO Fetch Mode Test
--DESCRIPTION--
Basic verification for "PDOStatement::setFetchMode()”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function FetchMode()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Set Fetch Mode";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val, grp";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), grp VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A', 'Group1'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'B', 'Group2'", null);

    // Execute test
    $tsql = "SELECT val, grp FROM [$tableName]";

    $stmt1 = $conn1->query($tsql, PDO::FETCH_NUM);
    var_dump($stmt1->fetchAll());
    unset($stmt1);

    $stmt1 = $conn1->query($tsql, PDO::FETCH_CLASS, 'Test');
    var_dump($stmt1->fetchAll());
    unset($stmt1);

    $stmt1 = $conn1->query($tsql, PDO::FETCH_NUM);
    $stmt1->setFetchMode(PDO::FETCH_CLASS, 'Test', array('Changed'));
    var_dump($stmt1->fetchAll());

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
        FetchMode();
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
  array(2) {
    [0]=>
    string(1) "A"
    [1]=>
    string(6) "Group1"
  }
  [1]=>
  array(2) {
    [0]=>
    string(1) "B"
    [1]=>
    string(6) "Group2"
  }
}
Test::__construct(N/A)
Test::__construct(N/A)
array(2) {
  [0]=>
  object(Test)#%d (2) {
    ["val"]=>
    string(1) "A"
    ["grp"]=>
    string(6) "Group1"
  }
  [1]=>
  object(Test)#%d (2) {
    ["val"]=>
    string(1) "B"
    ["grp"]=>
    string(6) "Group2"
  }
}
Test::__construct(Changed)
Test::__construct(Changed)
array(2) {
  [0]=>
  object(Test)#%d (2) {
    ["val"]=>
    string(1) "A"
    ["grp"]=>
    string(6) "Group1"
  }
  [1]=>
  object(Test)#%d (2) {
    ["val"]=>
    string(1) "B"
    ["grp"]=>
    string(6) "Group2"
  }
}
Test "PDO Statement - Set Fetch Mode" completed successfully.