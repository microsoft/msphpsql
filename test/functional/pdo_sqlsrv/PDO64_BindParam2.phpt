--TEST--
PDO Bind Param Test
--DESCRIPTION--
Verification for "PDOStatement::bindParam()".
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
    $dataCols = "id, label";
    $tableName = "pdo_test_table";
    createTable($conn1, $tableName, array(new ColumnMeta("int", "id", "NOT NULL PRIMARY KEY", "none"), "label" => "char(1)"));
    insertRow($conn1, $tableName, array("id" => 1, "label" => 'a'));
    insertRow($conn1, $tableName, array("id" => 2, "label" => 'b'));
    insertRow($conn1, $tableName, array("id" => 3, "label" => 'c'));
    insertRow($conn1, $tableName, array("id" => 4, "label" => 'd'));
    insertRow($conn1, $tableName, array("id" => 5, "label" => 'e'));
    insertRow($conn1, $tableName, array("id" => 6, "label" => 'f'));

    $id = null;
    $label = null;

    // Bind param @ SELECT
    $tsql1 = "SELECT TOP(2) id, label FROM [$tableName] WHERE id > ? ORDER BY id ASC";
    $value1 = 0;
    $stmt1 = $conn1->prepare($tsql1);
    bindParam(1, $stmt1, $value1);
    execStmt(1, $stmt1);
    bindColumn(1, $stmt1, $id, $label);
    fetchBound($stmt1, $id, $label);
    execStmt(1, $stmt1);
    fetchBound($stmt1, $id, $label);
    unset($stmt1);

    // Bind param @ INSERT
    $tsql2 = "INSERT INTO [$tableName](id, label) VALUES (100, ?)";
    $value2 = null;
    $stmt1 = $conn1->prepare($tsql2);
    bindParam(2, $stmt1, $value2);
    execStmt(2, $stmt1);
    unset($stmt1);

    // Check binding
    $tsql3 = "SELECT id, NULL AS _label FROM [$tableName] WHERE label IS NULL";
    $stmt1 = $conn1->query($tsql3);
    bindColumn(3, $stmt1, $id, $label);
    fetchBound($stmt1, $id, $label);
    unset($stmt1);

    // Cleanup
    dropTable($conn1, $tableName);
    unset($stmt1);
    unset($conn1);
} catch (Exception $e) {
    echo $e->getMessage();
}

function bindParam($offset, $stmt, &$value)
{
    if (!$stmt->bindParam(1, $value)) {
        logInfo($offset,"Cannot bind parameter");
    }
}

function bindColumn($offset, $stmt, &$param1, &$param2)
{
    if (!$stmt->bindColumn(1, $param1, PDO::PARAM_INT)) {
        logInfo($offset, "Cannot bind integer column");
    }
    if (!$stmt->bindColumn(2, $param2, PDO::PARAM_STR)) {
        logInfo($offset, "Cannot bind string column");
    }
}

function execStmt($offset, $stmt)
{
    if (!$stmt->execute()) {
        logInfo($offset, "Cannot execute statement");
    }
}


function fetchBound($stmt, &$param1, &$param2)
{
    while ($stmt->fetch(PDO::FETCH_BOUND)) {
        printf(
            "id = %s (%s) / label = %s (%s)\n",
                  var_export($param1, true),
                    gettype($param1),
                  var_export($param2, true),
                    gettype($param2)
        );
    }
}

function logInfo($offset, $msg)
{
    printf("[%03d] %s\n", $offset, $msg);
}
?>
--EXPECT--
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
id = 100 (integer) / label = NULL (NULL)
