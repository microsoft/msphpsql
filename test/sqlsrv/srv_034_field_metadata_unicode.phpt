--TEST--
Field metadata unicode
--SKIPIF--
--FILE--
<?php

require_once("autonomous_setup.php");

// Connect
$connectionInfo = array("UID"=>"$username", "PWD"=>"$password", "CharacterSet"=>"UTF-8");
$conn = sqlsrv_connect($serverName, $connectionInfo) ?: die();

// Create database
sqlsrv_query($conn,"CREATE DATABASE ". $dbName) ?: die();

// Create table. Column names: passport
$sql = "CREATE TABLE $tableName (पासपोर्ट CHAR(2), پاسپورٹ VARCHAR(2), Διαβατήριο VARCHAR(MAX))";
$stmt = sqlsrv_query($conn, $sql);

// Prepare the statement
$sql = "SELECT * FROM $tableName";
$stmt = sqlsrv_prepare($conn, $sql);

// Get and display field metadata
foreach(sqlsrv_field_metadata($stmt) as $meta)
{
	print_r($meta);
}

// DROP database
sqlsrv_query($conn,"DROP DATABASE ". $dbName);

// Free statement and connection resources
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

print "Done"
?>

--EXPECT--
Array
(
    [Name] => पासपोर्ट
    [Type] => 1
    [Size] => 2
    [Precision] => 
    [Scale] => 
    [Nullable] => 1
)
Array
(
    [Name] => پاسپورٹ
    [Type] => 12
    [Size] => 2
    [Precision] => 
    [Scale] => 
    [Nullable] => 1
)
Array
(
    [Name] => Διαβατήριο
    [Type] => 12
    [Size] => 0
    [Precision] => 
    [Scale] => 
    [Nullable] => 1
)
Done
