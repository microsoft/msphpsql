--TEST--
PDO Bind Value Test
--DESCRIPTION--
Verification for "PDOStatement::bindValue()".
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

	$testName = "PDO Statement - Bind Value";
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

	// Check different value bind modes
	$tsql1 = "SELECT TOP(2) id, label FROM [$tableName] WHERE id > ? ORDER BY id ASC";
	$stmt1 = PrepareQuery($conn1, $tsql1);

	printf("Binding value and not variable...\n");
	BindValue(1, $stmt1, 0);
	ExecStmt(1, $stmt1);
	BindColumn(1, $stmt1, $id, $label);
	FetchBound($stmt1, $id, $label);

	printf("Binding variable...\n");
	$var1 = 0;
	BindVar(2, $stmt1, $var1);
	ExecStmt(2, $stmt1);
	BindColumn(2, $stmt1, $id, $label);
	FetchBound($stmt1, $id, $label);

	printf("Binding variable which references another variable...\n");
	$var2 = 0;
	$var_ref = &$var2;
	BindVar(3, $stmt1, $var_ref);
	ExecStmt(3, $stmt1);
	BindColumn(3, $stmt1, $id, $label);
	FetchBound($stmt1, $id, $label);

	unset($stmt1);

	$tsql2 = "SELECT TOP(2) id, label FROM [$tableName] WHERE id > ? AND id <= ? ORDER BY id ASC";
	$stmt1 = PrepareQuery($conn1, $tsql2);

	printf("Binding a variable and a value...\n");
	$var3 = 0;
	BindMixed(4, $stmt1, $var3, 2);
	ExecStmt(4, $stmt1);
	BindColumn(4, $stmt1, $id, $label);
	FetchBound($stmt1, $id, $label);

	printf("Binding a variable to two placeholders and changing the variable value in between the binds...\n");
	$var4 = 0;
	$var5 = 2;
	BindPlaceholder(5, $stmt1, $var4, $var5);
	ExecStmt(5, $stmt1);
	BindColumn(5, $stmt1, $id, $label);
	FetchBound($stmt1, $id, $label);

	unset($stmt1);

	// Cleanup
	DropTable($conn1, $tableName);
	$stmt1 = null;
	$conn1 = null;

	EndTest($testName);
}

function BindValue($offset, $stmt, $value)
{
	if (!$stmt->bindValue(1, $value))
	{
		LogInfo($offset, "Cannot bind value");
	}
}

function BindVar($offset, $stmt, &$var)
{
	if (!$stmt->bindValue(1, $var))
	{
		LogInfo($offset, "Cannot bind variable");
	}
}


function BindMixed($offset, $stmt, &$var, $value)
{
	if (!$stmt->bindValue(1, $var))
	{
		LogInfo($offset, "Cannot bind variable");
	}
	if (!$stmt->bindValue(2, $value))
	{
		LogInfo($offset, "Cannot bind value");
	}
}

function BindPlaceholder($offset, $stmt, &$var1, &$var2)
{
	if (!$stmt->bindValue(1, $var1))
	{
		LogInfo($offset, "Cannot bind variable 1");
	}
	if (!$stmt->bindValue(2, $var2))
	{
		LogInfo($offset, "Cannot bind variable 2");
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
Test "PDO Statement - Bind Value" completed successfully.