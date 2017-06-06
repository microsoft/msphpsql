--TEST--
server side cursor specified when preparing
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

$tsql = "select * from HumanResources.Employee";
$stmt = sqlsrv_prepare( $conn, $tsql, array(), array("Scrollable"=>SQLSRV_CURSOR_CLIENT_BUFFERED));

if (! $stmt ) {
   echo "Statement could not be prepared.\n";
   die( print_r( sqlsrv_errors(), true));
}

sqlsrv_execute( $stmt);

$row_count = sqlsrv_num_rows( $stmt );
if ($row_count)
   echo "\nRow count = $row_count\n";

$row = sqlsrv_fetch($stmt, SQLSRV_SCROLL_FIRST);
if ($row ) {
   $EmployeeID = sqlsrv_get_field( $stmt, 0);
   echo "Employee ID = $EmployeeID \n";
}
?>
--EXPECT--
Row count = 290
Employee ID = 1