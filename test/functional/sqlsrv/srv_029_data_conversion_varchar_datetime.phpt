--TEST--
Data type precedence: conversion VARCHAR(n)
--SKIPIF--
--FILE--
<?php
require_once("MsCommon.inc");

// connect
$conn = connect();
if (!$conn) {
    fatalError("Connection could not be established.\n");
}

$tableName = GetTempTableName();

// Create table. Column names: passport
$sql = "CREATE TABLE $tableName (c1 VARCHAR(30))";
$stmt = sqlsrv_query($conn, $sql);

// Insert data. The data type with the lower precedence is
// converted to the data type with the higher precedence
$sql = "INSERT INTO $tableName VALUES (''),(-378.4),(43000.4),(GETDATE())";
$stmt = sqlsrv_query($conn, $sql);

// Read data from the table
$sql = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $sql);
for ($i = 0; $i < 3; $i++) {
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    var_dump($row[0]);
}

// Free statement and connection resources
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
string(19) "Jan  1 1900 12:00AM"
string(19) "Dec 18 1898  2:24PM"
string(19) "Sep 24 2017  9:36AM"
Done
