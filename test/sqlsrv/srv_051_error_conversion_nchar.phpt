--TEST--
Error checking for failed explicit data type conversions NCHAR(2)
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

// Connect
$connectionInfo = array("UID"=>$username, "PWD"=>$password, "CharacterSet"=>"UTF-8");
$conn = sqlsrv_connect($serverName, $connectionInfo) ?: die();

// Create database
sqlsrv_query($conn,"CREATE DATABASE ". $dbName) ?: die();

// Create table. Column names: passport
$sql = "CREATE TABLE $tableName (c1 NCHAR(2))";
$stmt = sqlsrv_query($conn, $sql);

// Insert data. Invalid statement
$sql = "INSERT INTO $tableName VALUES (10),(N'银河')";
$stmt = sqlsrv_query($conn, $sql);

// Get extended error
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
    [2] => [Microsoft][ODBC Driver 13 for SQL Server][SQL Server]Conversion failed when converting the nvarchar value '银河' to data type int.
    [message] => [Microsoft][ODBC Driver 13 for SQL Server][SQL Server]Conversion failed when converting the nvarchar value '银河' to data type int.
)
Done
