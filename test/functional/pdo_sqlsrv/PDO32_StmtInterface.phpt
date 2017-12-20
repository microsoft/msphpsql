--TEST--
PDOStatement Interface Test
--DESCRIPTION--
Verifies the compliance of the PDOStatement API Interface.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn1 = connect();
    $tableName = "pdo_test_table";
    createTable($conn1, $tableName, array(new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY"), "val" => "varchar(10)"));
    $stmt1 = $conn1->query("SELECT * FROM [$tableName]");

    checkInterface($stmt1);
    unset($stmt1);
    unset($conn1);
    echo "Test successfully completed\n";
} catch (Exception $e) {
    echo $e->getMessage();
}

function checkInterface($stmt)
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
    foreach ($methods as $k => $method) {
        if (isset($expected[$method])) {
            unset($expected[$method]);
            unset($methods[$k]);
        }
    }
    if (!empty($expected)) {
        printf("Dumping missing class methods\n");
        var_dump($expected);
    }
    if (!empty($methods)) {
        printf("Found more methods than expected, dumping list\n");
        var_dump($methods);
    }
}
?>
--EXPECT--
Test successfully completed
