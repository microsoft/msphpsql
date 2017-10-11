--TEST--
sqlsrv_fetch_array() using a scrollable cursor
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
$query = "CREATE TABLE $tableName (ID VARCHAR(10))";
$stmt = sqlsrv_query($conn, $query);

$query = "INSERT INTO $tableName VALUES ('1998.1'),('-2004'),('2016'),('4.2EUR')";
$stmt = sqlsrv_query($conn, $query) ?: die(print_r(sqlsrv_errors(), true));

// Fetch data
$query = "SELECT ID FROM $tableName";
$stmt = sqlsrv_query($conn, $query, [], array("Scrollable"=>"buffered"))
    ?: die(print_r(sqlsrv_errors(), true));

// Fetch first row
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_NEXT);
echo $row['ID']."\n";

// Fetch third row
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_ABSOLUTE, 2);
echo $row['ID']."\n";

// Fetch last row
$row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC, SQLSRV_SCROLL_LAST);
echo $row['ID']."\n";

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
print "Done"
?>

--EXPECT--
1998.1
2016
4.2EUR
Done
