--TEST--
PDO Transactions Test
--DESCRIPTION--
Basic verification for PDO Transactions.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function Transactions()
{
	include 'MsSetup.inc';

	$testName = "PDO - Transactions";
	StartTest($testName);

	$conn1 = Connect();

	// Prepare test table
	$dataCols = "id, val";
	CreateTableEx($conn1, $tableName, "id int NOT NULL PRIMARY KEY, val VARCHAR(10)", null);
	InsertRowEx($conn1, $tableName, $dataCols, "1, 'A'", null);
	InsertRowEx($conn1, $tableName, $dataCols, "2, 'B'", null);
	InsertRowEx($conn1, $tableName, $dataCols, "3, 'C'", null);

	// After initial INSERT ...
	$count = countRows($conn1, $tableName);
	echo "Rows after INSERT: $count.\n";

	// Transaction -> Automatic rollback on disconnect
	$conn1->beginTransaction();
	InsertRowEx($conn1, $tableName, $dataCols, "4, 'X'", null);
	InsertRowEx($conn1, $tableName, $dataCols, "5, 'Y'", null);
	$conn1 = null;	// disconnect without commit

	$conn1 = Connect();

	$count = countRows($conn1, $tableName);
	echo "Rows after ROLLBACK: $count.\n";

	// Transaction -> Commit
	$conn1->beginTransaction();
	InsertRowEx($conn1, $tableName, $dataCols, "4, 'D'", null);
	InsertRowEx($conn1, $tableName, $dataCols, "5, 'E'", null);
	InsertRowEx($conn1, $tableName, $dataCols, "6, 'F'", null);
	$conn1->commit();

	$count = countRows($conn1, $tableName);
	echo "Rows after COMMIT: $count.\n";

	// Cleanup
	DropTable($conn1, $tableName);
	$stmt1 = null;
	$conn1 = null;

	EndTest($testName);
}

function countRows($conn, $table)
{
	$stmt = ExecuteQuery($conn, "SELECT COUNT(*) FROM [$table]");
    	$res = $stmt->fetchColumn();
	unset($stmt);
    	return ($res);
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

	try
	{
		Transactions();
	}
	catch (Exception $e)
	{
		echo $e->getMessage();
	}
}

Repro();

?>
--EXPECTF--
Rows after INSERT: 3.
Rows after ROLLBACK: 3.
Rows after COMMIT: 6.
Test "PDO - Transactions" completed successfully.