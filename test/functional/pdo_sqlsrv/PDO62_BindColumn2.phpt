--TEST--
PDO Bind Column Test
--DESCRIPTION--
Verification for "PDOStatement::bindColumn()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require_once('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn1 = connect();

    // Prepare test table
    $tableName = "pdo_test_table";
    createTable($conn1, $tableName, array(new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY", "none"), "label" => "char(1)"));
    insertRow($conn1, $tableName, array("id" => 1, "label" => "a"));
    insertRow($conn1, $tableName, array("id" => 2, "label" => "b"));
    insertRow($conn1, $tableName, array("id" => 3, "label" => "c"));
    insertRow($conn1, $tableName, array("id" => 4, "label" => "d"));
    insertRow($conn1, $tableName, array("id" => 5, "label" => "e"));
    insertRow($conn1, $tableName, array("id" => 6, "label" => "f"));

    $rowCount = 3;
    $midRow = 4;

    // Check bind column
    $tsql1 = "SELECT TOP($rowCount) id, label FROM [$tableName] ORDER BY id ASC";
    $data = bindColumn($conn1, $tsql1);
    checkBind($conn1, $tsql1, $data);

    $tsql2 = "SELECT TOP($rowCount) id, label FROM (SELECT *, ROW_NUMBER() OVER(ORDER BY id ASC) as row FROM [$tableName]) [$tableName] WHERE row >= $midRow";
    $data = bindColumn($conn1, $tsql2);
    checkBind($conn1, $tsql2, $data);

    // Cleanup
    dropTable($conn1, $tableName);
    unset($conn1);
} catch (Exception $e) {
    echo $e->getMessage();
}

function bindColumn($conn, $tsql)
{
    $id = null;
    $label = null;
    $data = array();

    $stmt = $conn->prepare($tsql);
    $stmt->execute();
    if (!$stmt->bindColumn(1, $id, PDO::PARAM_INT)) {
        logInfo(1, "Cannot bind integer column");
    }
    if (!$stmt->bindColumn(2, $label, PDO::PARAM_STR)) {
        logInfo(1, "Cannot bind string column");
    }
    while ($stmt->fetch(PDO::FETCH_BOUND)) {
        printf("id = %s (%s) / label = %s (%s)\n",
               var_export($id, true), gettype($id),
               var_export($label, true), gettype($label));
        $data[] = array('id' => $id, 'label' => $label);
    }
    unset($stmt);

    return ($data);
}


function checkBind($conn, $tsql, $data)
{
    $index = 0;

    $stmt = ExecuteQuery($conn, $tsql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['id'] != $data[$index]['id']) {
            logInfo(2, "Fetch bound and fetch assoc differ - column 'id', row $index");
        }
        if ($row['label'] != $data[$index]['label']) {
            logInfo(2, "Fetch bound and fetch assoc differ - column 'label', row $index");
        }
        $index++;
    }
    unset($stmt);
}

function logInfo($offset, $msg)
{
    printf("[%03d] %s\n", $offset, $msg);
}

?>
--EXPECT--
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
id = 3 (integer) / label = 'c' (string)
id = 4 (integer) / label = 'd' (string)
id = 5 (integer) / label = 'e' (string)
id = 6 (integer) / label = 'f' (string)
