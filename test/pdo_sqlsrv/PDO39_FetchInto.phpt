--TEST--
PDO Fetch Test with FETCH_INTO mode
--DESCRIPTION--
Verification of fetch behavior with "PDO::FETCH_INTO" mode.
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

    $testName = "PDO Statement - Fetch Into";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), val2 VARCHAR(16)", null);
    $data = array(  array('10', 'Abc', 'zxy'),
            array('20', 'Def', 'wvu'),
            array('30', 'Ghi', 'tsr'),
            array('40', 'Jkl', 'qpo'),
            array('50', 'Mno', 'nml'),
            array('60', 'Pqr', 'kji'));

    // Insert data using question mark placeholders
    $stmt1 = PrepareQuery($conn1, "INSERT INTO [$tableName] VALUES(?, ?, ?)");
    foreach ($data as $row)
    {
        $stmt1->execute($row);
    }
    unset($stmt1);

    // Retrive data
    $stmt1 = PrepareQuery($conn1, "SELECT * FROM [$tableName]");

    echo "===SUCCESS===\n";
    $stmt1->setFetchMode(PDO::FETCH_INTO, new Test);
    $stmt1->execute();
    foreach($stmt1 as $obj)
    {
        var_dump($obj);
    }

    echo "===FAIL===\n";
    $stmt1->setFetchMode(PDO::FETCH_INTO, new Fail);
    $stmt1->execute();
    foreach($stmt1 as $obj)
    {
        var_dump($obj);
    }


    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}

class Test
{
    public $id, $val, $val2;
}

class Fail
{
    protected $id;
    public $val, $val2;
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
--EXPECTF--
===SUCCESS===
object(Test)#%d (3) {
  ["id"]=>
  string(2) "10"
  ["val"]=>
  string(3) "Abc"
  ["val2"]=>
  string(3) "zxy"
}
object(Test)#%d (3) {
  ["id"]=>
  string(2) "20"
  ["val"]=>
  string(3) "Def"
  ["val2"]=>
  string(3) "wvu"
}
object(Test)#%d (3) {
  ["id"]=>
  string(2) "30"
  ["val"]=>
  string(3) "Ghi"
  ["val2"]=>
  string(3) "tsr"
}
object(Test)#%d (3) {
  ["id"]=>
  string(2) "40"
  ["val"]=>
  string(3) "Jkl"
  ["val2"]=>
  string(3) "qpo"
}
object(Test)#%d (3) {
  ["id"]=>
  string(2) "50"
  ["val"]=>
  string(3) "Mno"
  ["val2"]=>
  string(3) "nml"
}
object(Test)#%d (3) {
  ["id"]=>
  string(2) "60"
  ["val"]=>
  string(3) "Pqr"
  ["val2"]=>
  string(3) "kji"
}
===FAIL===

Fatal error: Uncaught Error: Cannot access protected property Fail::$id in %sPDO39_FetchInto.php:%x
Stack trace:
#0 %sPDO39_FetchInto.php(%x): Fetch()
#1 %sPDO39_FetchInto.php(%x): Repro()
#2 {main}
  thrown in %sPDO39_FetchInto.php on line %x