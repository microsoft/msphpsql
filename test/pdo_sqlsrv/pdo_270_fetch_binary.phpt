--TEST--
Test fetch from binary, varbinary, varbinary(max), image columns, without setting binary encoding.
--DESCRIPTION--
Verifies GitHub issue 270 is fixed, users could not retrieve the data as inserted in binary columns without setting the binary encoding either on stmt or using bindCoulmn encoding.
This test verifies that the data inserted in binary columns can be retrieved using fetch, fetchColumn, fetchObject, and fetchAll functions.

--FILE--
<?php

require_once("autonomous_setup.php");

$tableName = 'test_binary'.rand();
$columns = array( 'col1', 'col2', 'col3', 'col4');

// Connect
$conn = new PDO( "sqlsrv:server=$serverName;Database=tempdb", $username, $password );

$sql = "CREATE TABLE $tableName ( $columns[0] binary(50), $columns[1] VARBINARY(50), $columns[2] VARBINARY(MAX), $columns[3] image)";
$conn->exec($sql);

$icon = base64_decode("This is some text to test retrieving from binary type columns");

// Insert data using bind parameters
$sql = "INSERT INTO $tableName($columns[0], $columns[1], $columns[2], $columns[3]) VALUES(?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bindParam(1, $icon, PDO::PARAM_LOB, null, PDO::SQLSRV_ENCODING_BINARY);
$stmt->bindParam(2, $icon, PDO::PARAM_LOB, null, PDO::SQLSRV_ENCODING_BINARY);
$stmt->bindParam(3, $icon, PDO::PARAM_LOB, null, PDO::SQLSRV_ENCODING_BINARY);
$stmt->bindParam(4, $icon, PDO::PARAM_LOB, null, PDO::SQLSRV_ENCODING_BINARY);
$stmt->execute();

// loop through each column in the table
foreach ($columns as $col){
	test_fetch($conn, $tableName, $col, $icon);
}
// DROP table
$conn->query("DROP TABLE $tableName") ?: die();

//free statement and connection
$stmt = null;
$conn = null;

print_r("Test finished successfully");

//calls various fetch methods
function test_fetch($conn, $tableName, $columnName, $input){
	
	$len = strlen($input);
	$result = "";
	$sql = "SELECT $columnName from $tableName";
	
	$stmt = $conn->query($sql);  
	$stmt->bindColumn(1, $result, PDO::PARAM_LOB);
	$stmt->fetch(PDO::FETCH_BOUND);
	//binary is fixed size, to evaluate output, compare it using strncmp
	if( strncmp($result, $input, $len) !== 0){
		print_r("\nRetrieving using bindColumn failed");
	}

	$result = "";
	$stmt = $conn->query($sql);      
	$stmt->bindColumn(1, $result, PDO::PARAM_LOB, 0 , PDO::SQLSRV_ENCODING_BINARY);
	$stmt->fetch(PDO::FETCH_BOUND);
	if( strncmp($result, $input, $len) !== 0){
		print_r("\nRetrieving using bindColumn with encoding set failed");
	}

	$result = "";
	$stmt = $conn->query($sql);  
	$result = $stmt->fetchColumn();
	if( strncmp($result, $input, $len) !== 0){
		print_r("\nRetrieving using fetchColumn failed");
	}

	$result = "";
	$stmt = $conn->query($sql);  
	$result = $stmt->fetchObject();
	if( strncmp($result->$columnName, $input, $len) !== 0){
		print_r("\nRetrieving using fetchObject failed");
	}

	$result = "";
	$stmt = $conn->query($sql);  
	$result = $stmt->fetchAll( PDO::FETCH_COLUMN );
	if( strncmp($result[0], $input, $len) !== 0){
		print_r("\nRetrieving using fetchAll failed");
	}
}

?>
--EXPECT--
Test finished successfully
