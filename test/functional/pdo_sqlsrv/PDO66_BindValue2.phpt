--TEST--
PDO Bind Value Test
--DESCRIPTION--
Verification for "PDOStatement::bindValue()".
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

    $id = null;
    $label = null;

    // Check different value bind modes
    $tsql1 = "SELECT TOP(2) id, label FROM [$tableName] WHERE id > ? ORDER BY id ASC";
    $stmt1 = $conn1->prepare($tsql1);

    printf("Binding value and not variable...\n");
    bindValue(1, $stmt1, 0);
    execStmt(1, $stmt1);
    bindColumn(1, $stmt1, $id, $label);
    fetchBound($stmt1, $id, $label);

    printf("Binding variable...\n");
    $var1 = 0;
    bindVar(2, $stmt1, $var1);
    execStmt(2, $stmt1);
    bindColumn(2, $stmt1, $id, $label);
    fetchBound($stmt1, $id, $label);

    printf("Binding variable which references another variable...\n");
    $var2 = 0;
    $var_ref = &$var2;
    bindVar(3, $stmt1, $var_ref);
    execStmt(3, $stmt1);
    bindColumn(3, $stmt1, $id, $label);
    fetchBound($stmt1, $id, $label);
    unset($stmt1);

    $tsql2 = "SELECT TOP(2) id, label FROM [$tableName] WHERE id > ? AND id <= ? ORDER BY id ASC";
    $stmt1 = $conn1->prepare($tsql2);

    printf("Binding a variable and a value...\n");
    $var3 = 0;
    bindMixed(4, $stmt1, $var3, 2);
    execStmt(4, $stmt1);
    bindColumn(4, $stmt1, $id, $label);
    fetchBound($stmt1, $id, $label);

    printf("Binding a variable to two placeholders and changing the variable value in between the binds...\n");
    $var4 = 0;
    $var5 = 2;
    bindPlaceholder(5, $stmt1, $var4, $var5);
    execStmt(5, $stmt1);
    bindColumn(5, $stmt1, $id, $label);
    fetchBound($stmt1, $id, $label);
    unset($stmt1);

    // Cleanup
    dropTable($conn1, $tableName);
    unset($stmt1);
    unset($conn1);
} catch (Exception $e) {
    echo $e->getMessage();
}

function bindValue($offset, $stmt, $value)
{
    if (!$stmt->bindValue(1, $value)) {
        logInfo($offset, "Cannot bind value");
    }
}

function bindVar($offset, $stmt, &$var)
{
    if (!$stmt->bindValue(1, $var)) {
        logInfo($offset, "Cannot bind variable");
    }
}


function bindMixed($offset, $stmt, &$var, $value)
{
    if (!$stmt->bindValue(1, $var)) {
        logInfo($offset, "Cannot bind variable");
    }
    if (!$stmt->bindValue(2, $value)) {
        logInfo($offset, "Cannot bind value");
    }
}

function bindPlaceholder($offset, $stmt, &$var1, &$var2)
{
    if (!$stmt->bindValue(1, $var1)) {
        logInfo($offset, "Cannot bind variable 1");
    }
    if (!$stmt->bindValue(2, $var2)) {
        logInfo($offset, "Cannot bind variable 2");
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
Binding value and not variable...
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
Binding variable...
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
Binding variable which references another variable...
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
Binding a variable and a value...
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
Binding a variable to two placeholders and changing the variable value in between the binds...
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
