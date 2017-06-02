--TEST--
Extending PDO Test #1
--DESCRIPTION--
Verification of capabilities for extending PDO.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Extend()
{
    include 'MsSetup.inc';

    $testName = "PDO - Extension";
    StartTest($testName);

    $conn1 = PDOConnect('ExPDO', $server, $uid, $pwd, true);
    $conn1->test();
    var_dump($conn1);

    // Prepare test table
    DropTable($conn1, $tableName);
    $conn1->query("CREATE TABLE [$tableName] (id int NOT NULL PRIMARY KEY, val VARCHAR(10))");
    $conn1->query("INSERT INTO [$tableName] VALUES(0, 'A')");
    $conn1->query("INSERT INTO [$tableName] VALUES(1, 'B')");

    // Retrieve test data via a direct query
    $stmt1 = ExecuteQuery($conn1, "SELECT val, id FROM [$tableName]");
    $result = $stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE);
    var_dump($result);


    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}

class ExPDO extends PDO
{
    public $test1 = 1;
 
    function __destruct()
    {
        echo __METHOD__ . "()\n";
        }

    function test()
    {
        $this->test2 = 2;
        var_dump($this->test1);
        var_dump($this->test2);
        $this->test2 = 22;
        }
    
    function query($sql)
    {
        echo __METHOD__ . "()\n";
        $stmt = parent::prepare($sql, array(PDO::ATTR_STATEMENT_CLASS=>array('ExPDOStatement')));
        $stmt->execute();
        return ($stmt);
    }
}

class ExPDOStatement extends PDOStatement
{
    protected function __construct()
    {
        echo __METHOD__ . "()\n";
    }

    function __destruct()
    {
        echo __METHOD__ . "()\n";
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
        Extend();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECTF--
int(1)
int(2)
object(ExPDO)#%d (2) {
  ["test1"]=>
  int(1)
  ["test2"]=>
  int(22)
}
ExPDO::query()
ExPDOStatement::__construct()
ExPDOStatement::__destruct()
ExPDO::query()
ExPDOStatement::__construct()
ExPDOStatement::__destruct()
ExPDO::query()
ExPDOStatement::__construct()
ExPDOStatement::__destruct()
ExPDO::query()
ExPDOStatement::__construct()
array(2) {
  ["A"]=>
  string(1) "0"
  ["B"]=>
  string(1) "1"
}
ExPDOStatement::__destruct()
ExPDO::__destruct()
Test "PDO - Extension" completed successfully.