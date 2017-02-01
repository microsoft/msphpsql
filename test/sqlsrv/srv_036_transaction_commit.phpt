--TEST--
Transaction operations: commit successful transactions
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

function PrintContent($conn)
{
	global $tableName;
	$query = "SELECT * FROM $tableName";
	$stmt = sqlsrv_query($conn, $query);
	// Fetch first row 
	$row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC);
	print_r($row);
}

// Connect
$conn = sqlsrv_connect( $serverName, $connectionInfo);
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Create database
sqlsrv_query($conn,"CREATE DATABASE ". $dbName) ?: die();

// Create table
$sql = "CREATE TABLE $tableName (
			GroupId VARCHAR(10) primary key, Accepted INT, 
			Tentative INT NOT NULL CHECK (Tentative >= 0))";
$stmt = sqlsrv_query($conn, $sql);


// Set initial data
$sql = "INSERT INTO $tableName VALUES ('ID1','12','5'),('ID102','20','1')";
$stmt = sqlsrv_query($conn, $sql) ?: die(print_r(sqlsrv_errors(), true));

//Initiate transaction
sqlsrv_begin_transaction($conn) ?: die(print_r( sqlsrv_errors(), true));

// Update parameters
$count = 4;
$groupId = "ID1";
$params = array($count, $groupId);

// Update Accepted column
$sql = "UPDATE $tableName SET Accepted = (Accepted + ?) WHERE GroupId = ?"; 
$stmt1 = sqlsrv_query( $conn, $sql, $params) ?: die(print_r(sqlsrv_errors(), true));

// Update Tentative column
$sql = "UPDATE $tableName SET Tentative = (Tentative - ?) WHERE GroupId = ?";
$stmt2 = sqlsrv_query($conn, $sql, $params);

// Commit the transactions
if ($stmt1 && $stmt2)
{
	sqlsrv_commit($conn);
}
else
{
	echo "\nERROR: $stmt1 and $stmt2 should be valid\n";
	sqlsrv_rollback($conn);
	echo "\nTransactions were rolled back.\n";
}

PrintContent($conn);

// DROP database
$stmt = sqlsrv_query($conn,"DROP DATABASE ". $dbName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
Array
(
    [GroupId] => ID1
    [Accepted] => 16
    [Tentative] => 1
)
Done

