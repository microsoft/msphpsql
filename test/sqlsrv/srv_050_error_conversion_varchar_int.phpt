--TEST--
Error checking for failed conversion: VARCHAR value 'null' to data type INT
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

// Connect
$conn = sqlsrv_connect($serverName, $connectionInfo) ?: die();

// Create database
sqlsrv_query($conn,"CREATE DATABASE ". $dbName) ?: die();

// Create table
$sql = "CREATE TABLE $tableName (ID INT)";
$stmt = sqlsrv_query($conn, $sql);

// Insert data. Wrong statement
$sql = "INSERT INTO $tableName VALUES (12),('null'),(-15)";
$stmt = sqlsrv_query($conn, $sql);

// Error checking
$err =  sqlsrv_errors();
print_r($err[0]);

// DROP database
sqlsrv_query($conn,"DROP DATABASE ". $dbName);

// Free statement and connection resources
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
Array
(
    [0] => 22018
    [SQLSTATE] => 22018
    [1] => 245
    [code] => 245
    [2] => [Microsoft][ODBC Driver 13 for SQL Server][SQL Server]Conversion failed when converting the varchar value 'null' to data type int.
    [message] => [Microsoft][ODBC Driver 13 for SQL Server][SQL Server]Conversion failed when converting the varchar value 'null' to data type int.
)
Done