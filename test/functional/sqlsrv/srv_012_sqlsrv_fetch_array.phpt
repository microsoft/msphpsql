--TEST--
sqlsrv_fetch_array() using a scrollable cursor
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

// connect
$conn = AE\connect();
$tableName = 'test012'; 

// Create table
$columns = array(new AE\ColumnMeta('VARCHAR(10)', 'ID'));
AE\createTable($conn, $tableName, $columns);

AE\insertRow($conn, $tableName, array("ID" => '1998.1'));
AE\insertRow($conn, $tableName, array("ID" => '-2004'));
AE\insertRow($conn, $tableName, array("ID" => '2016'));
AE\insertRow($conn, $tableName, array("ID" => '4.2EUR'));

// Fetch data
$query = "SELECT ID FROM $tableName";
$stmt = AE\executeQueryEx($conn, $query, array("Scrollable"=>"buffered"));

// Fetch first row
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_NEXT);
echo $row['ID']."\n";

// Fetch third row
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, 2);
echo $row['ID']."\n";

// Fetch last row
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_LAST);
echo $row['ID']."\n";

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
1998.1
2016
4.2EUR
Done
