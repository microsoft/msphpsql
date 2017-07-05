--TEST--
PDOStatement Interface Test
--DESCRIPTION--
Verifies the compliance of the PDOStatement API Interface.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function StmtInfo()
{
    include 'MsSetup.inc';

    $testName = "PDOStatement - Interface";
    StartTest($testName);

    $conn1 = Connect();
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10)", null);
    $stmt1 = ExecuteQuery($conn1, "SELECT * FROM [$tableName]");

    CheckInterface($stmt1);
    $stmt1 = null;
    $conn1 = null;

    EndTest($testName);
}

function CheckInterface($stmt)
{
    $expected = array(
        'errorCode'     => true,
        'errorInfo'     => true,
        'getAttribute'      => true,
        'setAttribute'      => true,
        'debugDumpParams'   => true,
        'bindColumn'        => true,
        'bindParam'     => true,
        'bindValue'     => true,
        'closeCursor'       => true,
        'columnCount'       => true,
        'execute'       => true,
        'setFetchMode'      => true,
        'fetch'         => true,
        'fetchAll'      => true,
        'fetchColumn'       => true,
        'fetchObject'       => true,
        'getColumnMeta'     => true,
        'nextRowset'        => true,
        'rowCount'      => true,
        '__wakeup'      => true,
        '__sleep'       => true,
    );
    $classname = get_class($stmt);
    $methods = get_class_methods($classname);
    foreach ($methods as $k => $method)
    {
        if (isset($expected[$method]))
        {
            unset($expected[$method]);
            unset($methods[$k]);
        }
    }
    if (!empty($expected))
    {
        printf("Dumping missing class methods\n");
        var_dump($expected);
    }
    if (!empty($methods))
    {
        printf("Found more methods than expected, dumping list\n");
        var_dump($methods);
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
        StmtInfo();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
Test "PDOStatement - Interface" completed successfully.