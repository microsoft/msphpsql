--TEST--
Data type precedence: conversion NVARCHAR(n)
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// connect
$conn = connect(array("CharacterSet"=>"UTF-8"));
if (!$conn) {
    fatalError("Connection could not be established.\n");
}

$tableName = GetTempTableName();

// Create table. Column names: passport
$sql = "CREATE TABLE $tableName (c1 NVARCHAR(8))";
$stmt = sqlsrv_query($conn, $sql);

// Insert data. The data type with the lower precedence
// is converted to the data type with the higher precedence
$sql = "INSERT INTO $tableName VALUES (3.1415),(-32),(null)";
$stmt = sqlsrv_query($conn, $sql);

// Insert more data
$sql = "INSERT INTO $tableName VALUES (''),('Galaxy'),('-- GO'),(N'银河系')";
$stmt = sqlsrv_query($conn, $sql);

// Read data from the table
$sql = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $sql);

while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
    var_dump($row[0]);
}

// Free statement and connection resources
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
string(6) "3.1415"
string(8) "-32.0000"
NULL
string(0) ""
string(6) "Galaxy"
string(5) "-- GO"
string(9) "银河系"
Done
