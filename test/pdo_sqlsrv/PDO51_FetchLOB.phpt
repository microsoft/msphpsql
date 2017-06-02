--TEST--
PDO Fetch LOB Test
--DESCRIPTION--
Verification for LOB handling.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function LobTest()
{
	include 'MsSetup.inc';

	$testName = "PDO Statement - Fetch LOB";
	StartTest($testName);

	$conn1 = Connect();

	// Execute test
	$data = str_repeat('A', 255);
	FetchLob(1, $conn1, $tableName, "VARCHAR(512)",   1, $data);
	FetchLob(2, $conn1, $tableName, "NVARCHAR(512)",  2, $data);
	unset($data);

	$data = str_repeat('B', 4000);
	FetchLob(3, $conn1, $tableName, "VARCHAR(8000)",  3, $data);
	FetchLob(4, $conn1, $tableName, "NVARCHAR(4000)", 4, $data);
	unset($data);

	$data = str_repeat('C', 100000);
	FetchLob(5, $conn1, $tableName, "TEXT",           5, $data);
	FetchLob(6, $conn1, $tableName, "NTEXT",          6, $data);
	unset($data);

	// Cleanup
	DropTable($conn1, $tableName);
	$conn1 = null;

	EndTest($testName);
}

function FetchLob($offset, $conn, $table, $sqlType, $data1, $data2)
{
	$id = NULL;
	$label = NULL;

	CreateTableEx($conn, $table, "id int NOT NULL PRIMARY KEY, label $sqlType", null);
	InsertRowEx($conn, $table, "id, label", "$data1, '$data2'", null);

	// Check data fetched with PDO::FETCH_BOUND
	$stmt = ExecuteQuery($conn, "SELECT * FROM [$table]");
	if (!$stmt->bindColumn(1, $id, PDO::PARAM_INT))
	{
		LogInfo($offset, "Cannot bind integer column");
	}
	if (!$stmt->bindColumn(2, $label, PDO::PARAM_LOB))
	{
		LogInfo($offset, "Cannot bind LOB column");
	}
	if (!$stmt->fetch(PDO::FETCH_BOUND))
	{
		LogInfo($offset, "Cannot fetch bound data");
	}
	if ($id != $data1)
	{
		LogInfo($offset, "ID data corruption: [$id] instead of [$data1]");
	}
	if ($label != $data2)
	{
		LogInfo($offset, "Label data corruption: [$label] instead of [$data2]");
	}
	unset($stmt);
	unset($label);

	// Check data fetched with PDO::FETCH_ASSOC
	$stmt = ExecuteQuery($conn, "SELECT * FROM [$table]");
	$refData = $stmt->fetch(PDO::FETCH_ASSOC);
	if ($refData['id'] != $data1)
	{
		$id = $refData['id'];
		LogInfo($offset, "ID data corruption: [$id] instead of [$data1]");

	}
	if ($refData['label'] != $data2)
	{
		$label = $refData['label'];
		LogInfo($offset, "Label data corruption: [$label] instead of [$data2]");
	}
	unset($stmt);
	unset($refData);
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
		LobTest();
	}
	catch (Exception $e)
	{
		echo $e->getMessage();
	}
}

Repro();

?>
--EXPECT--
Test "PDO Statement - Fetch LOB" completed successfully.