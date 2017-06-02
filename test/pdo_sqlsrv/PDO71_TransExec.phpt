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

	// After INSERT ...
	$count = countRows($conn1, $tableName);
	echo "Rows after INSERT: $count.\n";

	// Prepare DELETE query
	$stmt1 = PrepareQuery($conn1, "DELETE FROM [$tableName]");

	// Transaction -> Rollback
	$conn1->beginTransaction();
	$stmt1->execute();
	$count = countRows($conn1, $tableName);
	echo "Rows after DELETE: $count.\n";
	$conn1->rollBack();

	$count = countRows($conn1, $tableName);
	echo "Rows after ROLLBACK: $count.\n";

	// Transaction -> Commit
	$conn1->beginTransaction();
	$stmt1->execute();
	$count = countRows($conn1, $tableName);
	echo "Rows after DELETE: $count.\n";
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
Rows after DELETE: 0.
Rows after ROLLBACK: 3.
Rows after DELETE: 0.
Rows after COMMIT: 0.
Test "PDO - Transactions" completed successfully.