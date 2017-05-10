--TEST--
PDO Bind Column Test
--DESCRIPTION--
Verification for "PDOStatement::bindColumn".
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Fetch()
{
	include 'MsSetup.inc';

	$testName = "PDO Statement - Bind Column";
	StartTest($testName);

	$conn1 = Connect();

	// Prepare test table
	$dataCols = "idx, txt";
	CreateTableEx($conn1, $tableName, "idx int NOT NULL PRIMARY KEY, txt VARCHAR(20)", null);
	InsertRowEx($conn1, $tableName, $dataCols, "0, 'String0'", null);
	InsertRowEx($conn1, $tableName, $dataCols, "1, 'String1'", null);
	InsertRowEx($conn1, $tableName, $dataCols, "2, 'String2'", null);
	InsertRowEx($conn1, $tableName, $dataCols, "3, 'String3'", null);

	// Testing with prepared query
	LogInfo(1, "Testing fetchColumn() ...");
	$stmt1 = ExecuteQuery($conn1, "SELECT COUNT(idx) FROM [$tableName]");
	var_dump($stmt1->fetchColumn());
	unset($stmt1);

	LogInfo(2, "Testing fetchAll() ...");
	$stmt1 = PrepareQuery($conn1, "SELECT idx, txt FROM [$tableName] ORDER BY idx");
	$stmt1->execute();
	$data = $stmt1->fetchAll(PDO::FETCH_COLUMN|PDO::FETCH_UNIQUE);
	var_dump($data);

	LogInfo(3, "Testing bindColumn() ...");
	$stmt1->bindColumn('idx', $idx);
	$stmt1->bindColumn('txt', $txt);
	$stmt1->execute();
	while ($stmt1->fetch(PDO::FETCH_BOUND))
	{
		var_dump(array($idx=>$txt));
	}

	LogInfo(4, "Testing bindColumn() with data check ...");
	$id = null;
	$val = null;
	$data = array();
	$index = 0;
	if (!$stmt1->bindColumn(1, $id, PDO::PARAM_INT))
	{
		LogError(5, "Cannot bind integer column", $stmt1);
	}
	if (!$stmt1->bindColumn(2, $val, PDO::PARAM_STR))
	{
		LogError(5, "Cannot bind string column", $stmt1);
	}
	$stmt1->execute();
	while ($stmt1->fetch(PDO::FETCH_BOUND))
	{
		$data[] = array('id' => $id, 'val' => $val);
		printf("id = %s (%s) / val = %s (%s)\n",
			var_export($id, true), gettype($id),
			var_export($val, true), gettype($val));
	}
	unset($stmt1);
	$stmt1 = ExecuteQuery($conn1, "SELECT idx, txt FROM [$tableName] ORDER BY idx");
	while ($row = $stmt1->fetch(PDO::FETCH_ASSOC))
	{
		if ($row['idx'] != $data[$index]['id'])
		{
			LogInfo(6, "Data corruption for integer column in row $index");
		}
		if ($row['txt'] != $data[$index]['val'])
		{
			LogInfo(6, "Data corruption for string column in row $index");
		}
		$index++;
	}


	// Cleanup
	DropTable($conn1, $tableName);
	$stmt1 = null;
	$conn1 = null;

	EndTest($testName);
}

function LogInfo($offset, $msg)
{
	printf("[%03d] %s\n", $offset, $msg);
}

function LogError($offset, $msg, &$obj)
{
	printf("[%03d] %s: %s\n", $offset, $msg, $obj->errorCode);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

	try
	{
		Fetch();
	}
	catch (Exception $e)
	{
		echo $e->getMessage();
	}
}

Repro();

?>
--EXPECT--
[001] Testing fetchColumn() ...
string(1) "4"
[002] Testing fetchAll() ...
array(4) {
  [0]=>
  string(7) "String0"
  [1]=>
  string(7) "String1"
  [2]=>
  string(7) "String2"
  [3]=>
  string(7) "String3"
}
[003] Testing bindColumn() ...
array(1) {
  [0]=>
  string(7) "String0"
}
array(1) {
  [1]=>
  string(7) "String1"
}
array(1) {
  [2]=>
  string(7) "String2"
}
array(1) {
  [3]=>
  string(7) "String3"
}
[004] Testing bindColumn() with data check ...
id = 0 (integer) / val = 'String0' (string)
id = 1 (integer) / val = 'String1' (string)
id = 2 (integer) / val = 'String2' (string)
id = 3 (integer) / val = 'String3' (string)
Test "PDO Statement - Bind Column" completed successfully.