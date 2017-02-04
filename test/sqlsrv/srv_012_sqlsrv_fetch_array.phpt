--TEST--
sqlsrv_fetch_array() using a scrollable cursor
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
$query = "CREATE TABLE $tableName (ID VARCHAR(10))";
$stmt = sqlsrv_query($conn, $query);

$query = "INSERT INTO $tableName VALUES ('1998.1'),('-2004'),('2016'),('4.2EUR')";
$stmt = sqlsrv_query($conn, $query) ?: die( print_r( sqlsrv_errors(), true) );

// Fetch data
$query = "SELECT ID FROM $tableName";
$stmt = sqlsrv_query($conn, $query, [], array("Scrollable"=>"buffered"))
		?: die( print_r(sqlsrv_errors(), true));

// Fetch first row  
$row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC,SQLSRV_SCROLL_NEXT);
echo $row['ID']."\n";

// Fetch third row
$row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC,SQLSRV_SCROLL_ABSOLUTE,2);
echo $row['ID']."\n";

// Fetch last row
$row = sqlsrv_fetch_array($stmt,SQLSRV_FETCH_ASSOC,SQLSRV_SCROLL_LAST);
echo $row['ID']."\n";

// DROP database
$stmt = sqlsrv_query($conn,"DROP DATABASE ". $dbName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
1998.1
2016
4.2EUR
Done
