--TEST--
Character data type with non-ASCII characters
--DESCRIPTION--
For read/write non-ASCII characters on Windows and Linux the buffer
size may be different, 1 byte on Windows if 1252 code page
and 2 bytes on Linux if UTF-8 is used.
Example: the string Ð×Æ×Ø is 10 bytes on Linux, 5 bytes on Windows.
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
$tableName = '#srv_019test';
$stmt = sqlsrv_query($conn, "CREATE TABLE $tableName (c1 CHAR(5))");
if ($stmt === false) {
    die(print_r(sqlsrv_errors(), true));
}
sqlsrv_free_stmt($stmt);

// Insert data
$sql = "INSERT INTO $tableName VALUES ('I+PHP'),('Ð×Æ×Ø')";
$stmt = sqlsrv_query($conn, $sql);
sqlsrv_free_stmt($stmt);

// Query and print out
$sql = "SELECT c1 FROM $tableName";
$stmt = sqlsrv_query($conn, $sql);
if (!$stmt) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch the data
while (sqlsrv_fetch($stmt)) {
    echo sqlsrv_get_field($stmt, 0)."\n";
}

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
I+PHP
Ð×Æ×Ø
Done
