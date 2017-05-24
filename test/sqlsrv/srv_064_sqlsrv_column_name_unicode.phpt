--TEST--
Fetch array unicode column names
--SKIPIF--
--FILE--
<?php


include 'MsCommon.inc';
$tableName = "UnicodeColNameTest";

include 'MsSetup.inc';
Setup();
$conn = ConnectUTF8();

$tableName = "UnicodeColNameTest";

DropTable($conn, $tableName);

// Column name
$colName = "C1";         // WORKS
$colName = "C1ÐÐ";       // FETCH RETURNS AN EMPTY OUTPUT
// $colName = "星";         // FETCH RETURNS AN EMPTY OUTPUT

// Create table
$sql = "CREATE TABLE $tableName ($colName VARCHAR(10))";
sqlsrv_query($conn, $sql) ?: die( print_r(sqlsrv_errors(), true));

// Insert data
$sql = "INSERT INTO ".$tableName." VALUES ('Paris')";
$stmt = sqlsrv_query($conn, $sql);

// Fetch data
$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $query) ?: die( print_r(sqlsrv_errors(), true));

// Fetch
$row = sqlsrv_fetch_array($stmt);
echo $row[$colName]."\n";

DropTable($conn, $tableName);
// Close connection
sqlsrv_free_stmt( $stmt);
sqlsrv_close($conn);
print "Done";
?>

--EXPECT--
Paris
Done
