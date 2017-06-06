--TEST--
when there is a batch query, the number of rows is only available when use a client-side cursor.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
// $tsql = "select * from HumanResources.Department";

// Client-side cursor and batch statements
$tsql = "select top 8 * from HumanResources.EmployeePayHistory;select top 2 * from HumanResources.Employee;";

// works
$stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"buffered"));

// fails
// $stmt = sqlsrv_query($conn, $tsql);
// $stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"forward"));
// $stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"static"));
// $stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"keyset"));
// $stmt = sqlsrv_query($conn, $tsql, array(), array("Scrollable"=>"dynamic"));

$row_count = sqlsrv_num_rows( $stmt );
echo "<p>\nRow count first result set = $row_count<br>";

sqlsrv_next_result($stmt);

$row_count = sqlsrv_num_rows( $stmt );
echo "<p>\nRow count second result set = $row_count<br>";
?>
--EXPECT--
<p>
Row count first result set = 8<br><p>
Row count second result set = 2<br>