--TEST--
PDO Fetch Object Test
--DESCRIPTION--
Basic verification for "PDOStatement::fetchObject”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

class TestClass
{
    private $set_calls = 0;
    protected static $static_set_calls = 0;

    // NOTE: PDO does not care about protected
    protected $grp;

    // NOTE: PDO does not care about private and calls __construct() after __set()
    private function __construct($param1, $param2)
    {
        printf("TestClass::__construct(%s, %s): %d / %d\n", $param1, $param2,
            self::$static_set_calls, $this->set_calls);
    }

    // NOTE: PDO will call __set() prior to calling __construct()
    public function __set($prop, $value)
    {
        $this->inc();
        printf("TestClass::__set(%s, -%s-) %d\n",
            $prop, var_export($value, true), $this->set_calls, self::$static_set_calls);
            $this->{$prop} = $value;
    }

    // NOTE: PDO can call regular methods prior to calling __construct()
    public function inc()
    {
        $this->set_calls++;
        self::$static_set_calls++;
    }
}

function Fetch()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Fetch Object";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    // Prepare test table
    $dataCols = "id, label";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, label CHAR(1)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'a'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'b'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'c'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "4, 'd'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "5, 'e'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "6, 'f'", null);

    // Query test table and retrieve data
    $tsql = "SELECT TOP(3) * FROM [$tableName] ORDER BY id ASC";
    $stmt1 = PrepareQuery($conn1, $tsql);
    $stmt1->execute();

    $rowno = 0;
    $rows[] = array();
    while (is_object($rows[] = $stmt1->fetchObject('TestClass', array($rowno++, $rowno))))
    {
        printf("PDOStatement::fetchObject() was called for row $rowno\n");
    }
    var_dump($rows[$rowno - 1]);

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
TestClass::__set(id, -'1'-) 1
TestClass::__set(label, -'a'-) 2
TestClass::__construct(0, 1): 2 / 2
PDOStatement::fetchObject() was called for row 1
TestClass::__set(id, -'2'-) 1
TestClass::__set(label, -'b'-) 2
TestClass::__construct(1, 2): 4 / 2
PDOStatement::fetchObject() was called for row 2
TestClass::__set(id, -'3'-) 1
TestClass::__set(label, -'c'-) 2
TestClass::__construct(2, 3): 6 / 2
PDOStatement::fetchObject() was called for row 3
object(TestClass)#%d (4) {
  ["set_calls":"TestClass":private]=>
  int(2)
  ["grp":protected]=>
  NULL
  ["id"]=>
  string(1) "3"
  ["label"]=>
  string(1) "c"
}
Test "PDO Statement - Fetch Object" completed successfully.