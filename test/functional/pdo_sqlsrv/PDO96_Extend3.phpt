--TEST--
Extending PDO Test #2
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

    $data = array(  array('10', 'Abc', 'zxy'),
            array('20', 'Def', 'wvu'),
            array('30', 'Ghi', 'tsr'));

    $conn1 = PDOConnect('ExPDO', $server, $uid, $pwd, true);
    var_dump(get_class($conn1));

    // Prepare test table
    DropTable($conn1, $tableName);
    $conn1->exec("CREATE TABLE [$tableName] (id int NOT NULL PRIMARY KEY, val VARCHAR(10), val2 VARCHAR(16))");
    $stmt1 = $conn1->prepare("INSERT INTO [$tableName] VALUES(?, ?, ?)");
    var_dump(get_class($stmt1));
    foreach ($data as $row)
    {
        $stmt1->execute($row);
    }
    unset($stmt1);

    // Retrieve test data via a direct query
    $stmt1 = $conn1->query("SELECT * FROM [$tableName]");
    var_dump(get_class($stmt1));
    var_dump(get_class($stmt1->dbh));
    foreach($stmt1 as $obj)
    {
        var_dump($obj);
    }

    echo "===DONE===\n";

    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}

class ExPDO extends PDO
{
    function __destruct()
    {
        echo __METHOD__ . "()\n";
    }

    function query($sql)
    {
        echo __METHOD__ . "()\n";
        $stmt = $this->prepare($sql, array(PDO::ATTR_STATEMENT_CLASS=>array('ExPDOStatement', array($this))));
        $stmt->setFetchMode(PDO::FETCH_ASSOC);
        $stmt->execute();
        return ($stmt);
    }
}

class ExPDOStatement extends PDOStatement
{
    public $dbh;

    protected function __construct($dbh)
    {
        $this->dbh = $dbh;
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
--EXPECT--
string(5) "ExPDO"
string(12) "PDOStatement"
ExPDO::query()
ExPDOStatement::__construct()
string(14) "ExPDOStatement"
string(5) "ExPDO"
array(3) {
  ["id"]=>
  string(2) "10"
  ["val"]=>
  string(3) "Abc"
  ["val2"]=>
  string(3) "zxy"
}
array(3) {
  ["id"]=>
  string(2) "20"
  ["val"]=>
  string(3) "Def"
  ["val2"]=>
  string(3) "wvu"
}
array(3) {
  ["id"]=>
  string(2) "30"
  ["val"]=>
  string(3) "Ghi"
  ["val2"]=>
  string(3) "tsr"
}
===DONE===
ExPDOStatement::__destruct()
ExPDO::__destruct()
Test "PDO - Extension" completed successfully.