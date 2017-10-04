--TEST--
Extended ASCII column names: sqlsrv_fetch_array()
--DESCRIPTION--
Create a temporary table with column names that contain extended ASCII characters. Fetch data afterwards
using sqlsrv_fetch_array.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require_once("MsCommon.inc");

// Connect
$conn = connect(array( 'CharacterSet'=>'UTF-8' ));
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

// Create table
$tableName = '#srv_021test';
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (Cité NVARCHAR(10), Année SMALLINT)");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_free_stmt($stmt);

// Insert data
$sql1 = "INSERT INTO $tableName VALUES ('Paris', 1911)";
$stmt1 = sqlsrv_query($conn, $sql1);
sqlsrv_free_stmt($stmt1);

// Insert more data
$sql2 = "INSERT INTO $tableName VALUES ('London', 2012), ('Berlin', 1990)";
$stmt2 = sqlsrv_query($conn, $sql2);
sqlsrv_free_stmt($stmt2);

// Query
$sql = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $sql);
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}

echo sqlsrv_num_fields($stmt)."\n";

// Fetch array
while ($row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC)) {
    printf("%s %d\n", $row[0], $row[1]);
}

// Close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>

--EXPECT--
2
Paris 1911
London 2012
Berlin 1990
