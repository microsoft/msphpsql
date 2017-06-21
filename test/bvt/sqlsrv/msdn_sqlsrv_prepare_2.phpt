--TEST--
Prepares a statement and then re-execute it with different parameter values.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Define the parameterized query. */
$tsql = "UPDATE Sales.SalesOrderDetail
         SET OrderQty = ?
         WHERE SalesOrderDetailID = ?";

/* Initialize parameters and prepare the statement. Variables $qty
and $id are bound to the statement, $stmt1. */
$qty = 0; $id = 0;
$stmt1 = sqlsrv_prepare( $conn, $tsql, array( &$qty, &$id));
if( $stmt1 )
{
     echo "Statement 1 prepared.<br>";
} 
else 
{
     echo "Error in statement preparation.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Set up the SalesOrderDetailID and OrderQty information. This array
maps the order ID to order quantity in key=>value pairs. */
$orders = array( 20=>10, 21=>20, 22=>30);

/* Execute the statement for each order. */
foreach( $orders as $id => $qty)
{
     // Because $id and $qty are bound to $stmt1, their updated
     // values are used with each execution of the statement. 
     if( sqlsrv_execute( $stmt1) === false )
     {
          echo "Error in statement execution.<br>";
          die( print_r( sqlsrv_errors(), true));
     }
}
echo "Orders updated.<br>";

/* Free $stmt1 resources.  This allows $id and $qty to be bound to a different statement.*/
sqlsrv_free_stmt( $stmt1);

/* Now verify that the results were successfully written by selecting 
the newly inserted rows. */
$tsql = "SELECT OrderQty 
         FROM Sales.SalesOrderDetail 
         WHERE SalesOrderDetailID = ?";

/* Prepare the statement. Variable $id is bound to $stmt2. */
$stmt2 = sqlsrv_prepare( $conn, $tsql, array( &$id));
if( $stmt2 )
{
     echo "Statement 2 prepared.<br>";
} 
else 
{
     echo "Error in statement preparation.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Execute the statement for each order. */
foreach( array_keys($orders) as $id)
{
     /* Because $id is bound to $stmt2, its updated value 
        is used with each execution of the statement. */
     if( sqlsrv_execute( $stmt2))
     {
          sqlsrv_fetch( $stmt2);
          $quantity = sqlsrv_get_field( $stmt2, 0);
          echo "Order $id is for $quantity units.<br>";
     }
     else
     {
          echo "Error in statement execution.<br>";
          die( print_r( sqlsrv_errors(), true));
     }
}

/* revert the update */
$r_sql3 = "UPDATE Sales.SalesOrderDetail SET OrderQty = 2 WHERE SalesOrderDetailID = 20";
$stmt3 = sqlsrv_query($conn, $r_sql3);
$r_sql4 = "UPDATE Sales.SalesOrderDetail SET OrderQty = 3 WHERE SalesOrderDetailID = 21";
$stmt4 = sqlsrv_query($conn, $r_sql4);
$r_sql5 = "UPDATE Sales.SalesOrderDetail SET OrderQty = 2 WHERE SalesOrderDetailID = 22";
$stmt5 = sqlsrv_query($conn, $r_sql5);

/* Free $stmt2 and connection resources. */
sqlsrv_free_stmt( $stmt2);
sqlsrv_free_stmt( $stmt3);
sqlsrv_free_stmt( $stmt4);
sqlsrv_free_stmt( $stmt5);
sqlsrv_close( $conn);
?>
--EXPECT--
Statement 1 prepared.<br>Orders updated.<br>Statement 2 prepared.<br>Order 20 is for 10 units.<br>Order 21 is for 20 units.<br>Order 22 is for 30 units.<br>