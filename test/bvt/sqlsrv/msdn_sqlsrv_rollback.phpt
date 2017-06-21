--TEST--
Rolls back the current transaction on the specified connection and returns the connection to the auto-commit mode.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true ));
}

/* Initiate transaction. */
/* Exit script if transaction cannot be initiated. */
if ( sqlsrv_begin_transaction( $conn) === false )
{
     echo "Could not begin transaction.\n";
     die( print_r( sqlsrv_errors(), true ));
}

/* Initialize parameter values. */
$orderId = 43659; $qty = 5; $productId = 709;
$offerId = 1; $price = 5.70;

/* Set up and execute the first query. */
$tsql1 = "INSERT INTO Sales.SalesOrderDetail 
                     (SalesOrderID, 
                      OrderQty, 
                      ProductID, 
                      SpecialOfferID, 
                      UnitPrice)
          VALUES (?, ?, ?, ?, ?)";
$params1 = array( $orderId, $qty, $productId, $offerId, $price);
$stmt1 = sqlsrv_query( $conn, $tsql1, $params1 );

/* Set up and executee the second query. */
$tsql2 = "UPDATE Production.ProductInventory 
          SET Quantity = (Quantity - ?) 
          WHERE ProductID = ?";
$params2 = array($qty, $productId);
$stmt2 = sqlsrv_query( $conn, $tsql2, $params2 );

/* If both queries were successful, commit the transaction. */
/* Otherwise, rollback the transaction. */
if( $stmt1 && $stmt2 )
{
     sqlsrv_commit( $conn );
     echo "Transaction was committed.\n";
}
else
{
     sqlsrv_rollback( $conn );
     echo "Transaction was rolled back.\n";
}

/* Revert the changes */
$d_sql = "DELETE FROM Sales.SalesOrderDetail WHERE SalesOrderID=43659 AND OrderQty=5 AND ProductID=709 AND SpecialOfferID=1 AND Unitprice=5.70";
$stmt3 = sqlsrv_query($conn, $d_sql); 

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt1);
sqlsrv_free_stmt( $stmt2);
sqlsrv_free_stmt($stmt3);
sqlsrv_close( $conn);
?>
--EXPECT--
Transaction was committed.