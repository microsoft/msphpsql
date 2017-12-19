--TEST--
PDO Bind Param Truncation Test
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
    $tableName = "pdo_test_table";
    createTable($conn1, $tableName, array(new ColumnMeta("int", "id", "IDENTITY NOT NULL"), "class" => "int", "value" => "char(32)"));
    $conn1->exec("CREATE CLUSTERED INDEX [idx_test_int] ON $tableName (id)");
    $tsql = "INSERT INTO [$tableName] (class, value) VALUES(:class, :value)";

    $id = 0;
    $class = 0;
    $value = '';

    // Prepare insert query$
    $stmt1 = $conn1->prepare($tsql);
    bindParam(1, $stmt1, ':class', $class);
    bindParam(2, $stmt1, ':value', $value);
        
    // Insert test rows
    $class = 4;
    $value = '2011';
    execStmt(1, $stmt1);

    $class = 5;
    $value = 'Sat, 20 Mar 10 21:29:13 -0600';
    execStmt(2, $stmt1);

    $class = 6;
    $value = 'Fri, 07 May 10 11:35:32 -0600';
    execStmt(3, $stmt1);

    unset($stmt1);

    // Check data
    $id = 0;
    $value = '';
    $tsql = "SELECT id, value FROM [$tableName] ORDER BY id";
    $stmt2 = $conn1->query($tsql);
    bindColumn(1, $stmt2, $id, $value);
    while ($stmt2->fetch(PDO::FETCH_BOUND)) {
        printf(
            "id = %s (%s) / value = %s (%s)\n",
                 var_export($id, true),
                 gettype($id),
                 var_export($value, true),
                 gettype($value)
        );
    }
    unset($stmt2);

    // Cleanup
    dropTable($conn1, $tableName);
    unset($stmt1);
    unset($conn1);
} catch (Exception $e) {
    echo $e->getMessage();
}

function bindParam($offset, $stmt, $param, &$value)
{
    if (!$stmt->bindParam($param, $value)) {
        logInfo($offset, "Cannot bind parameter $param");
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
        var_dump($stmt->errorInfo());
    }
}

function logInfo($offset, $msg)
{
    printf("[%03d] %s\n", $offset, $msg);
}
?>
--EXPECT--
id = 1 (integer) / value = '2011                            ' (string)
id = 2 (integer) / value = 'Sat, 20 Mar 10 21:29:13 -0600   ' (string)
id = 3 (integer) / value = 'Fri, 07 May 10 11:35:32 -0600   ' (string)
