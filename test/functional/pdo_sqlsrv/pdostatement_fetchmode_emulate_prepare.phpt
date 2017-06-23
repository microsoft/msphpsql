--TEST--
PDO Fetch Mode Test with emulate prepare 
--DESCRIPTION--
Basic verification for "PDOStatement::setFetchMode()”.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
include 'MsCommon.inc';

function FetchMode()
{
	include 'MsSetup.inc';

	$testName = "PDO Statement - Set Fetch Mode";
	StartTest($testName);
	
	$dsn = "sqlsrv:Server=$server ; Database = $databaseName";
	$conn1 = new PDO($dsn, $uid, $pwd);
    $conn1->setAttribute( PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION );

	// Prepare test table
	CreateTableEx($conn1, $tableName, "ID int NOT NULL PRIMARY KEY, Policy VARCHAR(2), Label VARCHAR(10), Budget MONEY", null);

	try {
		$res = $conn1->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
		if ($res)
		{
			echo "setAttribute should have failed.\n\n";
		}
	}
	catch (Exception $e)
	{
		echo $e->getMessage();
	}

	echo "\n";
	
	try {
		$query = "SELECT * FROM [$tableName]";
		$stmt = $conn1->query($query);
		$stmt->setAttribute(PDO::ATTR_EMULATE_PREPARES, true);
	}
	catch (Exception $e)
	{
		echo $e->getMessage();
	}

	echo "\nStart inserting data...\n";
	$dataCols = "ID, Policy, Label";
	$query = "INSERT INTO [$tableName](ID, Policy, Label, Budget) VALUES (?, ?, ?, ?)";
	$stmt = $conn1->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => true));
	$stmt = $conn1->prepare($query, array(PDO::ATTR_EMULATE_PREPARES => false));
	for ($i = 1; $i <= 2; $i++)
	{
		$pol = chr(64+$i);
		$grp = "Group " . $i;
		$budget = $i * 1000 + $i * 15;
		$stmt->execute( array( $i, $pol, $grp, $budget ) );
	}

	$query1 = "INSERT INTO [$tableName](ID, Policy, Label, Budget) VALUES (:col1, :col2, :col3, :col4)";
	$stmt = $conn1->prepare($query1, array(PDO::ATTR_EMULATE_PREPARES => true));
	for ($i = 3; $i <= 5; $i++)
	{
		$pol = chr(64+$i);
		$grp = "Group " . $i;
		$budget = $i * 1000 + $i * 15;
		$stmt->execute( array( ':col1' => $i, ':col2' => $pol, ':col3' => $grp, ':col4' => $budget ) );
	}
	echo "....Done....\n";
	echo "Now selecting....\n";
	$tsql = "SELECT * FROM [$tableName]";
	$stmt1 = $conn1->prepare($tsql, array(PDO::ATTR_EMULATE_PREPARES => false));
	$stmt1 = $conn1->prepare($tsql, array(PDO::ATTR_EMULATE_PREPARES => true, PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL));
	$stmt1->execute();
	var_dump($stmt1->fetch( PDO::FETCH_ASSOC ));
	$row = $stmt1->fetch( PDO::FETCH_NUM, PDO::FETCH_ORI_NEXT );  
	print "$row[1]\n";
	$row = $stmt1->fetch( PDO::FETCH_LAZY, PDO::FETCH_ORI_LAST );  
	print "$row[3]\n";
	$row = $stmt1->fetch( PDO::FETCH_BOTH, PDO::FETCH_ORI_PRIOR );  
	print_r($row);

	echo "\nFirst two groups or Budget > 4000....\n";
	$tsql = "SELECT * FROM [$tableName] WHERE ID <= :id OR Budget > :budget";
	$stmt2 = $conn1->prepare($tsql, array(PDO::ATTR_EMULATE_PREPARES => true));
	$budget = 4000;
	$id = 2;
	$stmt2->bindParam(':id', $id);
	$stmt2->bindParam(':budget', $budget);
	$stmt2->execute();
	while ( $result = $stmt2->fetchObject() ){
		print_r($result);
		echo "\n";  
	}
	
	echo "\nSelect Policy = 'A'....\n";
	$tsql = "SELECT * FROM [$tableName] WHERE Policy = ?";	
	$stmt3 = $conn1->prepare($tsql, array(PDO::ATTR_EMULATE_PREPARES => true));
	$pol = 'A';
	$stmt3->bindValue(1, $pol);
	$id = 'C';
	$stmt3->execute();
	while ( $row = $stmt3->fetch( PDO::FETCH_ASSOC ) ){  
		print_r($row);  
		echo "\n";  
	}  
	
	echo "\nSelect id > 2....\n";
	$tsql = "SELECT Policy, Label, Budget FROM [$tableName] WHERE ID > 2";
	$stmt4 = $conn1->prepare($tsql, array(PDO::ATTR_EMULATE_PREPARES => true));
	$stmt4->execute();
	$stmt4->bindColumn('Policy', $policy);
	$stmt4->bindColumn('Budget', $budget);
	while ( $row = $stmt4->fetch( PDO::FETCH_BOUND ) ){  
		echo "Policy: $policy\tBudget: $budget\n";  
	}  

	echo "\nBudget Metadata....\n";
	$metadata = $stmt4->getColumnMeta(2);  
	var_dump($metadata);  

	// Cleanup
	DropTable($conn1, $tableName);
	$stmt1 = null;
	$stmt2 = null;
	$stmt3 = null;
	$stmt4 = null;
	$conn1 = null;

	EndTest($testName);
}

class Test
{
	function __construct($name = 'N/A')
	{
		echo __METHOD__ . "($name)\n";
	}
}


//--------------------------------------------------------------------
// Repro
//
//--------------------------------------------------------------------
function Repro()
{

	try
	{
		FetchMode();
	}
	catch (Exception $e)
	{
		echo $e->getMessage();
	}
}

Repro();

?>
--EXPECT--
SQLSTATE[IMSSP]: The given attribute is only supported on the PDOStatement object.
SQLSTATE[IMSSP]: An invalid attribute was designated on the PDOStatement object.
Start inserting data...
....Done....
Now selecting....
array(4) {
  ["ID"]=>
  string(1) "1"
  ["Policy"]=>
  string(1) "A"
  ["Label"]=>
  string(7) "Group 1"
  ["Budget"]=>
  string(9) "1015.0000"
}
B
5075.0000
Array
(
    [ID] => 4
    [0] => 4
    [Policy] => D
    [1] => D
    [Label] => Group 4
    [2] => Group 4
    [Budget] => 4060.0000
    [3] => 4060.0000
)

First two groups or Budget > 4000....
stdClass Object
(
    [ID] => 1
    [Policy] => A
    [Label] => Group 1
    [Budget] => 1015.0000
)

stdClass Object
(
    [ID] => 2
    [Policy] => B
    [Label] => Group 2
    [Budget] => 2030.0000
)

stdClass Object
(
    [ID] => 4
    [Policy] => D
    [Label] => Group 4
    [Budget] => 4060.0000
)

stdClass Object
(
    [ID] => 5
    [Policy] => E
    [Label] => Group 5
    [Budget] => 5075.0000
)


Select Policy = 'A'....
Array
(
    [ID] => 1
    [Policy] => A
    [Label] => Group 1
    [Budget] => 1015.0000
)


Select id > 2....
Policy: C	Budget: 3045.0000
Policy: D	Budget: 4060.0000
Policy: E	Budget: 5075.0000

Budget Metadata....
array(8) {
  ["flags"]=>
  int(0)
  ["sqlsrv:decl_type"]=>
  string(5) "money"
  ["native_type"]=>
  string(6) "string"
  ["table"]=>
  string(0) ""
  ["pdo_type"]=>
  int(2)
  ["name"]=>
  string(6) "Budget"
  ["len"]=>
  int(19)
  ["precision"]=>
  int(4)
}
Test "PDO Statement - Set Fetch Mode" completed successfully.