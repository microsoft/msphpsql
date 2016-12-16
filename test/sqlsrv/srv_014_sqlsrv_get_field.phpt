--TEST--
sqlsrv_get_field() using SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY)
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

// Connect
$conn = sqlsrv_connect( $serverName, $connectionInfo);
if( !$conn ) { die( print_r( sqlsrv_errors(), true)); }

// Create database
sqlsrv_query($conn,"CREATE DATABASE ". $dbName) ?: die();

// Create table
$query = "CREATE TABLE ".$tableName." (ID VARCHAR(10))";
$stmt = sqlsrv_query($conn, $query);

$query = "INSERT INTO ".$tableName." VALUES ('1998.1'),('-2004.2436'),('4.2EUR')";
$stmt = sqlsrv_query($conn, $query) ?: die( print_r( sqlsrv_errors(), true) );

// Fetch data
$query = "SELECT * FROM ".$tableName;
$stmt = sqlsrv_query( $conn, $query ) ?: die( print_r( sqlsrv_errors(), true) );

while(sqlsrv_fetch($stmt)) {
	$field = sqlsrv_get_field($stmt,0,SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
	var_dump($field);
	
	while(!feof($field))
	{
		echo fread($field, 100)."\n";
	}
}

// DROP database
$stmt = sqlsrv_query($conn,"DROP DATABASE ". $dbName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
resource(10) of type (stream)
1998.1
resource(11) of type (stream)
-2004.2436
resource(12) of type (stream)
4.2EUR
Done
