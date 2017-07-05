--TEST--
PDO Bind Param Test
--DESCRIPTION--
Verification for "PDOStatement::bindParam()".
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

	$testName = "PDO Statement - Bind Param";
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

	$id = null;
	$label = null;

	// Bind param @ SELECT
	$tsql1 = "SELECT TOP(2) id, label FROM [$tableName] WHERE id > ? ORDER BY id ASC";
	$value1 = 0; 
	$stmt1 = PrepareQuery($conn1, $tsql1);
	BindParam(1, $stmt1, $value1);
	ExecStmt(1, $stmt1);
	BindColumn(1, $stmt1, $id, $label);
	FetchBound($stmt1, $id, $label);
	ExecStmt(1, $stmt1);
	FetchBound($stmt1, $id, $label);
	unset($stmt1);

	// Bind param @ INSERT
	$tsql2 = "INSERT INTO [$tableName](id, label) VALUES (100, ?)";
	$value2 = null; 
	$stmt1 = PrepareQuery($conn1, $tsql2);
	BindParam(2, $stmt1, $value2);
	ExecStmt(2, $stmt1);
	unset($stmt1);

	// Check binding
	$tsql3 = "SELECT id, NULL AS _label FROM [$tableName] WHERE label IS NULL";
	$stmt1 = ExecuteQuery($conn1, $tsql3);
	BindColumn(3, $stmt1, $id, $label);
	FetchBound($stmt1, $id, $label);
	unset($stmt1);

	// Cleanup
	DropTable($conn1, $tableName);
	$stmt1 = null;
	$conn1 = null;

	EndTest($testName);
}

function BindParam($offset, $stmt, &$value)
{
	if (!$stmt->bindParam(1, $value))
	{
		LogInfo($offset,"Cannot bind parameter");
	}
}

function BindColumn($offset, $stmt, &$param1, &$param2)
{
	if (!$stmt->bindColumn(1, $param1, PDO::PARAM_INT))
	{
		LogInfo($offset, "Cannot bind integer column");
	}
	if (!$stmt->bindColumn(2, $param2, PDO::PARAM_STR))
	{
		LogInfo($offset, "Cannot bind string column");
	}
}

function ExecStmt($offset, $stmt)
{
	if (!$stmt->execute())
	{
		LogInfo($offset, "Cannot execute statement");
	}
}


function FetchBound($stmt, &$param1, &$param2)
{
	while ($stmt->fetch(PDO::FETCH_BOUND))
	{
		printf("id = %s (%s) / label = %s (%s)\n",
	 		var_export($param1, true), gettype($param1),
			var_export($param2, true), gettype($param2));
	}
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
id = 1 (integer) / label = 'a' (string)
id = 2 (integer) / label = 'b' (string)
id = 100 (integer) / label = NULL (NULL)
Test "PDO Statement - Bind Param" completed successfully.