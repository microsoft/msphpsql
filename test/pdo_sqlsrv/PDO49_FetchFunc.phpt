--TEST--
PDO Fetch Test with PDO::FETCH_FUNC
--DESCRIPTION--
Basic verification for "PDOStatement::fetchAll(PDO::FETCH_FUNC)”.
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

    // Prepare test tables
    $dataCols = "id, val, grp";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10), grp VARCHAR(10)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'A', 'Group1'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'B', 'Group1'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'C', 'Group2'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "4, 'D', 'Group2'", null);

    $f = new Test1(0, 0);

    // Query table and retrieve data
    $stmt1 = PrepareQuery($conn1, "SELECT grp, id FROM [$tableName]");
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_FUNC|PDO::FETCH_GROUP, 'test'));
    unset($stmt1);

    $stmt1 = PrepareQuery($conn1, "SELECT id, val FROM [$tableName]");
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_FUNC, 'test'));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_FUNC, array('Test1','factory')));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_FUNC, array($f, 'factory')));
    unset($stmt1);

    $stmt1 = $conn1->prepare("SELECT id, val FROM [$tableName]",
                 array(PDO::ATTR_STATEMENT_CLASS=>array('DerivedStatement',
                                    array('Overloaded', $conn1))));
    var_dump(get_class($stmt1));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_FUNC, array($stmt1, 'retrieve')));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_FUNC, array($stmt1, 'reTrieve')));
    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_FUNC, array($stmt1, 'RETRIEVE')));


    // Cleanup
    DropTable($conn1, $tableName);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}

class DerivedStatement extends PDOStatement
{
    private function __construct($name, $conn)
    {
        $this->name = $name;
        echo __METHOD__ . "($name)\n";
    }

    function reTrieve($id, $val) {
        echo __METHOD__ . "($id,$val)\n";
        return array($id=>$val);
    }
}

class Test1
{
    public function __construct($id, $val)
    {
        echo __METHOD__ . "($id,$val)\n";
        $this->id = $id;
        $this->val = $val;
    }

    static public function factory($id, $val)
    {
        echo __METHOD__ . "($id,$val)\n";
        return new self($id, $val);
    }
}

function test($id,$val='N/A')
{
    echo __METHOD__ . "($id,$val)\n";
    return array($id=>$val);
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
--EXPECTF--
Test1::__construct(0,0)
test(1,N/A)
test(2,N/A)
test(3,N/A)
test(4,N/A)
array(2) {
  ["Group1"]=>
  array(2) {
    [0]=>
    array(1) {
      [1]=>
      string(3) "N/A"
    }
    [1]=>
    array(1) {
      [2]=>
      string(3) "N/A"
    }
  }
  ["Group2"]=>
  array(2) {
    [0]=>
    array(1) {
      [3]=>
      string(3) "N/A"
    }
    [1]=>
    array(1) {
      [4]=>
      string(3) "N/A"
    }
  }
}
test(1,A)
test(2,B)
test(3,C)
test(4,D)
array(4) {
  [0]=>
  array(1) {
    [1]=>
    string(1) "A"
  }
  [1]=>
  array(1) {
    [2]=>
    string(1) "B"
  }
  [2]=>
  array(1) {
    [3]=>
    string(1) "C"
  }
  [3]=>
  array(1) {
    [4]=>
    string(1) "D"
  }
}
Test1::factory(1,A)
Test1::__construct(1,A)
Test1::factory(2,B)
Test1::__construct(2,B)
Test1::factory(3,C)
Test1::__construct(3,C)
Test1::factory(4,D)
Test1::__construct(4,D)
array(4) {
  [0]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "1"
    ["val"]=>
    string(1) "A"
  }
  [1]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "2"
    ["val"]=>
    string(1) "B"
  }
  [2]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "3"
    ["val"]=>
    string(1) "C"
  }
  [3]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "4"
    ["val"]=>
    string(1) "D"
  }
}
Test1::factory(1,A)
Test1::__construct(1,A)
Test1::factory(2,B)
Test1::__construct(2,B)
Test1::factory(3,C)
Test1::__construct(3,C)
Test1::factory(4,D)
Test1::__construct(4,D)
array(4) {
  [0]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "1"
    ["val"]=>
    string(1) "A"
  }
  [1]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "2"
    ["val"]=>
    string(1) "B"
  }
  [2]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "3"
    ["val"]=>
    string(1) "C"
  }
  [3]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "4"
    ["val"]=>
    string(1) "D"
  }
}
DerivedStatement::__construct(Overloaded)
string(16) "DerivedStatement"
DerivedStatement::reTrieve(1,A)
DerivedStatement::reTrieve(2,B)
DerivedStatement::reTrieve(3,C)
DerivedStatement::reTrieve(4,D)
array(4) {
  [0]=>
  array(1) {
    [1]=>
    string(1) "A"
  }
  [1]=>
  array(1) {
    [2]=>
    string(1) "B"
  }
  [2]=>
  array(1) {
    [3]=>
    string(1) "C"
  }
  [3]=>
  array(1) {
    [4]=>
    string(1) "D"
  }
}
DerivedStatement::reTrieve(1,A)
DerivedStatement::reTrieve(2,B)
DerivedStatement::reTrieve(3,C)
DerivedStatement::reTrieve(4,D)
array(4) {
  [0]=>
  array(1) {
    [1]=>
    string(1) "A"
  }
  [1]=>
  array(1) {
    [2]=>
    string(1) "B"
  }
  [2]=>
  array(1) {
    [3]=>
    string(1) "C"
  }
  [3]=>
  array(1) {
    [4]=>
    string(1) "D"
  }
}
DerivedStatement::reTrieve(1,A)
DerivedStatement::reTrieve(2,B)
DerivedStatement::reTrieve(3,C)
DerivedStatement::reTrieve(4,D)
array(4) {
  [0]=>
  array(1) {
    [1]=>
    string(1) "A"
  }
  [1]=>
  array(1) {
    [2]=>
    string(1) "B"
  }
  [2]=>
  array(1) {
    [3]=>
    string(1) "C"
  }
  [3]=>
  array(1) {
    [4]=>
    string(1) "D"
  }
}
Test "PDO Statement - Fetch All" completed successfully.