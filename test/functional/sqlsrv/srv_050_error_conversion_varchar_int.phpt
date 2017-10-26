--TEST--
Error checking for failed conversion: VARCHAR value 'null' to data type INT
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

// Create table
$sql = "CREATE TABLE $tableName (ID INT)";
$stmt = sqlsrv_query($conn, $sql);

// Insert data. Wrong statement
$sql = "INSERT INTO $tableName VALUES (12),('null'),(-15)";
$stmt = sqlsrv_query($conn, $sql);

// Error checking
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
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Conversion failed when converting the varchar value 'null' to data type int\.
    \[message\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Conversion failed when converting the varchar value 'null' to data type int\.
\)
Done
