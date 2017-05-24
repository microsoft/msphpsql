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
	CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val1 VARCHAR(10), val2 VARCHAR(10), val3 VARCHAR(10)", null);
	$data = array("one", "two", "three");

	// Insert test data
	$stmt1 = PrepareQuery($conn1, "INSERT INTO [$tableName] VALUES(1, ?, ?, ?)");
	foreach ($data as $i => $v)
	{
		$stmt1->bindValue($i+1, $v);
	}
	$stmt1->execute();
	unset($stmt1);

	// Retrieve test data
	$stmt1 = PrepareQuery($conn1, "SELECT * FROM [$tableName]");
	$stmt1->execute();
	var_dump($stmt1->fetchAll(PDO::FETCH_ASSOC));


	// Cleanup
	DropTable($conn1, $tableName);
	$stmt1 = null;
	$conn1 = null;

	EndTest($testName);
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
array(1) {
  [0]=>
  array(4) {
    ["id"]=>
    string(1) "1"
    ["val1"]=>
    string(3) "one"
    ["val2"]=>
    string(3) "two"
    ["val3"]=>
    string(5) "three"
  }
}
Test "PDO Statement - Bind Value" completed successfully.