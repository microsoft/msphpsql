--TEST--
Create database that already exists using unicode name
--SKIPIF--
--FILE--
<?php
require_once("autonomous_setup.php");

// Connect
$conn = sqlsrv_connect($serverName, $connectionInfo);

// Check if connected
if( !$conn ) {
     die( print_r( sqlsrv_errors(), true));
}

// Set database name
$dbUniqueName = "uniqueDB_银河系";

// DROP database if exists
$stmt = sqlsrv_query($conn,"IF EXISTS(SELECT name FROM sys.databases WHERE name = '"
	.$dbUniqueName."') DROP DATABASE ".$dbUniqueName);

// var_dump($stmt);
sqlsrv_free_stmt($stmt);

// exit(0);

// CREATE database
$stmt = sqlsrv_query($conn,"CREATE DATABASE ". $dbUniqueName);

if( $stmt === false)
{
    printf("%-20s%10s\n","CREATE DATABASE","FAILED");
	die( print_r( sqlsrv_errors(), true ));
}


// CREATE database that already exists
$stmt = sqlsrv_query($conn,"CREATE DATABASE ". $dbUniqueName);
var_dump($stmt);
if( $stmt === false)
{
	$res = array_values(sqlsrv_errors());
	var_dump($res[0]['SQLSTATE']);
	var_dump($res[0][1]);
	var_dump($res[0][2]);
}
else {
	 printf("%-20s\n","ERROR: CREATE database MUST return bool(false)");
}

// DROP database
sqlsrv_query($conn,"IF EXISTS(SELECT name FROM sys.databases WHERE name = '"
	.$dbUniqueName."') DROP DATABASE ".$dbUniqueName);

sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
bool(false)
string(5) "42000"
int(1801)
string(133) "[Microsoft][ODBC Driver 13 for SQL Server][SQL Server]Database 'uniqueDB_银河系' already exists. Choose a different database name."
Done
