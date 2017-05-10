--TEST--
PDO Statement Iterator Test
--DESCRIPTION--
Verification of PDOStatement with an iterator.
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

    $testName = "PDO Statement - Iterator";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, val, grp";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), grp VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A', 'Group1'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'B', 'Group2'", null);

    $tsql = "SELECT val, grp FROM [$tableName]";

    // Testing with direct query
    /*$stmt1 = $conn1->query($tsql, PDO::FETCH_CLASS, 'Test', array('WOW'));
    $iter = new IteratorIterator($stmt1);
    foreach ($iter as $data)
    {
        var_dump($data);
    }

    $iter->next();          // allowed
    var_dump($iter->current()); // should return NULL
    var_dump($iter->valid());*/     // should must return
    unset($stmt1);

    // Testing with prepared query
    $stmt1 = $conn1->prepare($tsql, array(PDO::ATTR_STATEMENT_CLASS=>array('PDOStatementAggregate')));
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

class PDOStatementAggregate extends PDOStatement implements IteratorAggregate
{
    private function __construct()
    {
        echo __METHOD__ . "\n";
        $this->setFetchMode(PDO::FETCH_NUM);   
    }

    function getIterator()
    {
        echo __METHOD__ . "\n";
        $this->execute();
        return (new IteratorIterator($this, 'PDOStatement'));
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
PDOStatementAggregate::__construct
PDOStatementAggregate::getIterator
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
Test "PDO Statement - Iterator" completed successfully.