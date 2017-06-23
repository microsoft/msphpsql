--TEST--
PDO Bind Column Test
--DESCRIPTION--
Verification for "PDOStatement::bindColumn()".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Bind()
{
    include 'MsSetup.inc';

    $testName = "PDO Statement - Bind Column";
    StartTest($testName);

    $conn1 = Connect();

    // Prepare test table
    $dataCols = "id, label";
    CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, label CHAR(1)", null);
    InsertRowEx($conn1, $tableName, $dataCols, "1, 'a'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "2, 'b'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "3, 'c'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "4, 'd'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "5, 'e'", null);
    InsertRowEx($conn1, $tableName, $dataCols, "6, 'f'", null);

    $rowCount = 3;
    $midRow = 4;

    // Check bind column
    $tsql1 = "SELECT TOP($rowCount) id, label FROM [$tableName] ORDER BY id ASC";
    $data = BindColumn($conn1, $tsql1);
    CheckBind($conn1, $tsql1, $data);

    $tsql2 = "SELECT TOP($rowCount) id, label FROM (SELECT *, ROW_NUMBER() OVER(ORDER BY id ASC) as row FROM [$tableName]) [$tableName] WHERE row >= $midRow";
    $data = BindColumn($conn1, $tsql2);
    CheckBind($conn1, $tsql2, $data);

    // Cleanup
    DropTable($conn1, $tableName);
    $conn1 = null;

    EndTest($testName);
}

function BindColumn($conn, $tsql)
{
    $id = null;
    $label = null;
    $data = array();

    $stmt = PrepareQuery($conn, $tsql);
    $stmt->execute();
    if (!$stmt->bindColumn(1, $id, PDO::PARAM_INT))
    {
        LogInfo(1, "Cannot bind integer column");
    }
    if (!$stmt->bindColumn(2, $label, PDO::PARAM_STR))
    {
        LogInfo(1, "Cannot bind string column");
    }
    while ($stmt->fetch(PDO::FETCH_BOUND))
    {
        printf("id = %s (%s) / label = %s (%s)\n",
            var_export($id, true), gettype($id),
            var_export($label, true), gettype($label));
        $data[] = array('id' => $id, 'label' => $label);
    }
    unset($stmt);

    return ($data);
}


function CheckBind($conn, $tsql, $data)
{
    $index = 0;

    $stmt = ExecuteQuery($conn, $tsql);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC))
    {
        if ($row['id'] != $data[$index]['id'])
        {
            LogInfo(2, "Fetch bound and fetch assoc differ - column 'id', row $index");
        }
        if ($row['label'] != $data[$index]['label'])
        {
            LogInfo(2, "Fetch bound and fetch assoc differ - column 'label', row $index");
        }
        $index++;
    }
    unset($stmt);
}

function LogInfo($offset, $msg)
{
    printf("[%03d] %s\n", $offset, $msg);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

    try
    {
        Bind();
    }
    catch (Exception $e)
    {
        echo $e->getMessage();
    }
}

Repro();

?>
--EXPECT--
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
id = 3 (integer) / label = 'c' (string)
id = 4 (integer) / label = 'd' (string)
id = 5 (integer) / label = 'e' (string)
id = 6 (integer) / label = 'f' (string)
Test "PDO Statement - Bind Column" completed successfully.