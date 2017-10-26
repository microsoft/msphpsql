--TEST--
Fetch array of extended ASCII data using a scrollable buffered cursor
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
$tableName = '#exAsciiTest';
$query = "CREATE TABLE $tableName (ID CHAR(10))";
$stmt = sqlsrv_query($conn, $query);

// Insert data
$query = "INSERT INTO $tableName VALUES ('Aå_Ð×Æ×Ø_B')";
$stmt = sqlsrv_query($conn, $query);
if (! $stmt) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch data
$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $query, [], array("Scrollable"=>"buffered"));
if (! $stmt) {
    die(print_r(sqlsrv_errors(), true));
}

// Fetch
$row = sqlsrv_fetch_array($stmt);
var_dump($row);

// Close connection
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
array(2) {
  [0]=>
  string(16) "Aå_Ð×Æ×Ø_B"
  ["ID"]=>
  string(16) "Aå_Ð×Æ×Ø_B"
}
Done
