--TEST--
Error checking for failed explicit data type conversions NCHAR(2)
--SKIPIF--
--FILE--
<?php

require_once("MsCommon.inc");

// connect
$conn = connect(array("CharacterSet"=>"utf-8"));
if (!$conn) {
    printErrors("Connection could not be established.\n");
}

$tableName = GetTempTableName();

// Create table. Column names: passport
$sql = "CREATE TABLE $tableName (c1 NCHAR(2))";
$stmt = sqlsrv_query($conn, $sql);

// Insert data. Invalid statement
$sql = "INSERT INTO $tableName VALUES (10),(N'银河')";
$stmt = sqlsrv_query($conn, $sql);

// Get extended error
$err =  sqlsrv_errors();
print_r($err[0]);

// Free statement and connection resources
sqlsrv_close($conn);

print "Done"
?>

--EXPECTREGEX--
Array
\(
    \[0\] => 22018
    \[SQLSTATE\] => 22018
    \[1\] => 245
    \[code\] => 245
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Conversion failed when converting the nvarchar value '银河' to data type int\.
    \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Conversion failed when converting the nvarchar value '银河' to data type int\.
\)
Done
