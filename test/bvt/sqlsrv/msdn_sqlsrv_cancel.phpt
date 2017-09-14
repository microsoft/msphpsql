--TEST--
executes a query, then comsumes and counts results until reaches a specified amount. The remaining query results are then discarded.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Prepare and execute the query. */
$tsql = "SELECT OrderQty, UnitPrice FROM Sales.SalesOrderDetail ORDER BY SalesOrderID";
$stmt = sqlsrv_prepare( $conn, $tsql);
if( $stmt === false )
{
     echo "Error in statement preparation.\n";
     die( print_r( sqlsrv_errors(), true));
}
if( sqlsrv_execute( $stmt ) === false)
{
     echo "Error in statement execution.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Initialize tracking variables. */
$salesTotal = 0;
$count = 0;

/* Count and display the number of sales that produce revenue
of $100,000. */
while( ($row = sqlsrv_fetch_array( $stmt)) && $salesTotal <=100000)
{
     $qty = $row[0];
     $price = $row[1];
     $salesTotal += ( $price * $qty);
     $count++;
}
echo "$count sales accounted for the first $$salesTotal in revenue.\n";

/* Cancel the pending results. The statement can be reused. */
sqlsrv_cancel( $stmt);
?>
--EXPECTREGEX--
[3-5][0-9] sales accounted for the first \$10[0-9]{4}\.[0-9]{2,4} in revenue.
