--TEST--
PDO Recursive Iterator Test
--DESCRIPTION--
Verification of PDOStatement with a recursive iterator.
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

    $testName = "PDO Statement - Recursive Iterator";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test tables
    $dataCols = "id, val, val2";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), val2 VARCHAR(16)", null);
    $data = array(  array('10', 'Abc', 'zxy'),
            array('20', 'Def', 'wvu'),
            array('30', 'Ghi', 'tsr'));


    // Insert using question mark placeholders
    $stmt1 = PrepareQuery($conn1, "INSERT INTO [$tableName] VALUES(?, ?, ?)");
    foreach ($data as $row)
    {
        $stmt1->execute($row);
    }
    unset($stmt1);

    echo "===QUERY===\n";
    $stmt1 = ExecuteQuery($conn1, "SELECT * FROM [$tableName]");
    foreach(new RecursiveTreeIterator(new RecursiveArrayIterator($stmt1->fetchAll(PDO::FETCH_ASSOC)),
                      RecursiveTreeIterator::BYPASS_KEY) as $c=>$v)
    {
        echo "$v [$c]\n";
    }

    echo "===DONE===\n";

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
--EXPECT--
===QUERY===
|-Array [0]
| |-10 [id]
| |-Abc [val]
| \-zxy [val2]
|-Array [1]
| |-20 [id]
| |-Def [val]
| \-wvu [val2]
\-Array [2]
  |-30 [id]
  |-Ghi [val]
  \-tsr [val2]
===DONE===
Test "PDO Statement - Recursive Iterator" completed successfully.