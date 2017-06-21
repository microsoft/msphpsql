--TEST--
client side buffered cursor
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if ( $conn === false ) {
   echo "Could not connect.\n";
   die( print_r( sqlsrv_errors(), true));
}

$tsql = "select * from HumanResources.Department";

// Execute the query with client-side cursor.
$stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"buffered"));
if (! $stmt) {
   echo "Error in statement execution.\n";
   die( print_r( sqlsrv_errors(), true));
}

// row count is always available with a client-side cursor
$row_count = sqlsrv_num_rows( $stmt );
echo "\nRow count = $row_count\n";

// Move to a specific row in the result set.
$row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);
$EmployeeID = sqlsrv_get_field( $stmt, 0);
echo "Employee ID = $EmployeeID \n";

// Client-side cursor and batch statements
$tsql = "select top 2 * from HumanResources.Employee;Select top 3 * from HumanResources.EmployeePayHistory";

$stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"buffered"));
if (! $stmt) {
   echo "Error in statement execution.\n";
   die( print_r( sqlsrv_errors(), true));
}

$row_count = sqlsrv_num_rows( $stmt );
echo "\nRow count for first result set = $row_count\n";

$row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);
$EmployeeID = sqlsrv_get_field( $stmt, 0);
echo "Employee ID = $EmployeeID \n";

sqlsrv_next_result($stmt);

$row_count = sqlsrv_num_rows( $stmt );
echo "\nRow count for second result set = $row_count\n";

$row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_LAST);
$BusinessEntityID = sqlsrv_get_field( $stmt, 0);
echo "Business Entity ID = $BusinessEntityID \n";
?>
--EXPECT--
Row count = 16
Employee ID = 1 

Row count for first result set = 2
Employee ID = 1 

Row count for second result set = 3
Business Entity ID = 3