--TEST--
PDO Fetch Test with PDO::FETCH_CLASSTYPE
--DESCRIPTION--
Basic verification for "PDOStatement::fetchAll(PDO::FETCH_CLASSTYPE)”.
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
    $dataCols1 = "id, name";
    $table1 = $tableName."1";
    CreateTableEx($conn1, $table1, "id int NOT NULL PRIMARY KEY, name VARCHAR(10) NOT NULL UNIQUE", null);
    InsertRowEx($conn1, $table1, $dataCols1, "0, 'stdClass'", null);
    InsertRowEx($conn1, $table1, $dataCols1, "1, 'Test1'", null);
    InsertRowEx($conn1, $table1, $dataCols1, "2, 'Test2'", null);

    $dataCols2 = "id, classtype, val";
    $table2 = $tableName."2";
    CreateTableEx($conn1, $table2, "id int NOT NULL PRIMARY KEY, classtype int, val VARCHAR(10)", null);
    InsertRowEx($conn1, $table2, $dataCols2, "1, 0, 'A'", null);
    InsertRowEx($conn1, $table2, $dataCols2, "2, 1, 'B'", null);
    InsertRowEx($conn1, $table2, $dataCols2, "3, 2, 'C'", null);
    InsertRowEx($conn1, $table2, $dataCols2, "4, 3, 'D'", null);

    $dataCols3 = "id, classtype, val, grp";
    $table3 = $tableName."3";
    CreateTableEx($conn1, $table3, "id int NOT NULL PRIMARY KEY, classtype int, val VARCHAR(10), grp VARCHAR(10)", null);
    InsertRowEx($conn1, $table3, $dataCols3, "1, 0, 'A', 'Group1'", null);
    InsertRowEx($conn1, $table3, $dataCols3, "2, 1, 'B', 'Group1'", null);
    InsertRowEx($conn1, $table3, $dataCols3, "3, 2, 'C', 'Group2'", null);
    InsertRowEx($conn1, $table3, $dataCols3, "4, 3, 'D', 'Group2'", null);

    // Test fetchAll(PDO::FETCH_CLASSTYPE)
    $stmt1 = PrepareQuery($conn1, "SELECT $table1.name, $table2.id AS id, $table2.val AS val FROM $table2 LEFT JOIN $table1 ON $table2.classtype=$table1.id");

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_NUM));

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_CLASSTYPE, 'Test3'));
    unset($stmt1);

    // Test fetchAll(PDO::FETCH_CLASSTYPE) with GROUP/UNIQUE
    $stmt1 = PrepareQuery($conn1, "SELECT $table1.name, $table3.grp AS grp, $table3.id AS id, $table3.val AS val FROM $table3 LEFT JOIN $table1 ON $table3.classtype=$table1.id");

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_CLASSTYPE|PDO::FETCH_GROUP, 'Test3'));

    $stmt1->execute();
    var_dump($stmt1->fetchAll(PDO::FETCH_CLASS|PDO::FETCH_CLASSTYPE|PDO::FETCH_UNIQUE, 'Test3'));

    // Cleanup
    DropTable($conn1, $table1);
    DropTable($conn1, $table2);
    DropTable($conn1, $table3);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}

class Test1
{
    public function __construct()
    {
        echo __METHOD__ . "()\n";
    }
}

class Test2
{
    public function __construct()
    {
        echo __METHOD__ . "()\n";
    }
}

class Test3
{
    public function __construct()
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
array(4) {
  [0]=>
  array(3) {
    [0]=>
    string(8) "stdClass"
    [1]=>
    string(1) "1"
    [2]=>
    string(1) "A"
  }
  [1]=>
  array(3) {
    [0]=>
    string(5) "Test1"
    [1]=>
    string(1) "2"
    [2]=>
    string(1) "B"
  }
  [2]=>
  array(3) {
    [0]=>
    string(5) "Test2"
    [1]=>
    string(1) "3"
    [2]=>
    string(1) "C"
  }
  [3]=>
  array(3) {
    [0]=>
    NULL
    [1]=>
    string(1) "4"
    [2]=>
    string(1) "D"
  }
}
Test1::__construct()
Test2::__construct()
Test3::__construct()
array(4) {
  [0]=>
  object(stdClass)#%d (2) {
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
  object(Test2)#%d (2) {
    ["id"]=>
    string(1) "3"
    ["val"]=>
    string(1) "C"
  }
  [3]=>
  object(Test3)#%d (2) {
    ["id"]=>
    string(1) "4"
    ["val"]=>
    string(1) "D"
  }
}
Test1::__construct()
Test2::__construct()
Test3::__construct()
array(2) {
  ["Group1"]=>
  array(2) {
    [0]=>
    object(stdClass)#%d (2) {
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
  }
  ["Group2"]=>
  array(2) {
    [0]=>
    object(Test2)#%d (2) {
      ["id"]=>
      string(1) "3"
      ["val"]=>
      string(1) "C"
    }
    [1]=>
    object(Test3)#%d (2) {
      ["id"]=>
      string(1) "4"
      ["val"]=>
      string(1) "D"
    }
  }
}
Test1::__construct()
Test2::__construct()
Test3::__construct()
array(2) {
  ["Group1"]=>
  object(Test1)#%d (2) {
    ["id"]=>
    string(1) "2"
    ["val"]=>
    string(1) "B"
  }
  ["Group2"]=>
  object(Test3)#%d (2) {
    ["id"]=>
    string(1) "4"
    ["val"]=>
    string(1) "D"
  }
}
Test "PDO Statement - Fetch All" completed successfully.