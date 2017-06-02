--TEST--
delete in a transaction
--SKIPIF--

--FILE--
<?php
/* Connect to the local server using Windows Authentication and
specify the AdventureWorks database as the database in use. */
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Begin transaction. */
if( sqlsrv_begin_transaction($conn) === false ) 
{ 
     echo "Could not begin transaction.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Set the Order ID.  */
$orderId = 43667;

/* Execute operations that are part of the transaction. Commit on
success, roll back on failure. */
if (perform_trans_ops($conn, $orderId))
{
     //If commit fails, roll back the transaction.
     if(sqlsrv_commit($conn))
     {
         echo "Transaction committed.\n";
     }
     else
     {
         echo "Commit failed - rolling back.\n";
         sqlsrv_rollback($conn);
     }
}
else
{
     "Error in transaction operation - rolling back.\n";
     sqlsrv_rollback($conn);
}

/*Free connection resources*/
sqlsrv_close( $conn);
/*----------------  FUNCTION: perform_trans_ops  -----------------*/
function perform_trans_ops($conn, $orderId)
{
    /* Define query to update inventory based on sales order info. */
    $tsql1 = "UPDATE Production.ProductInventory 
              SET Quantity = Quantity + s.OrderQty 
              FROM Production.ProductInventory p 
              JOIN Sales.SalesOrderDetail s 
              ON s.ProductID = p.ProductID 
              WHERE s.SalesOrderID = ?";

    /* Define the parameters array. */
    $params = array($orderId);

    /* Execute the UPDATE statement. Return false on failure. */
    if( sqlsrv_query( $conn, $tsql1, $params) === false ) return false;

    /* Delete the sales order. Return false on failure */
    $tsql2 = "DELETE FROM Sales.SalesOrderDetail 
              WHERE SalesOrderID = ?";
    if(sqlsrv_query( $conn, $tsql2, $params) === false ) return false;

    /* Return true because all operations were successful. */
    return true;
}
?>
--EXPECT--
Transaction committed.