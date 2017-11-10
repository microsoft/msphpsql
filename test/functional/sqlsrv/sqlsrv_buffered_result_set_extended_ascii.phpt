--TEST--
Fetch array of extended ASCII data using a scrollable buffered cursor
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

// Connect
$conn = AE\connect(array('CharacterSet'=>'UTF-8'));

// Create table
$tableName = 'exAsciiTest';
$columns = array(new AE\ColumnMeta('CHAR(10)', 'ID'));
AE\createTable($conn, $tableName, $columns);

// Insert data
$res = null;
$stmt = AE\insertRow($conn, $tableName, array('ID' => 'Aå_Ð×Æ×Ø_B'));

// Fetch data
$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $query, [], array("Scrollable"=>"buffered"));

// Fetch
$row = sqlsrv_fetch_array($stmt);
var_dump($row);

dropTable($conn, $tableName);

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
