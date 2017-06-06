--TEST--
binding of variables using prepare function
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

$tsql = "INSERT INTO Sales.SalesOrderDetail (SalesOrderID, 
                                             OrderQty, 
                                             ProductID, 
                                             SpecialOfferID, 
                                             UnitPrice)
         VALUES (?, ?, ?, ?, ?)";

/* Each sub array here will be a parameter array for a query.
The values in each sub array are, in order, SalesOrderID, OrderQty,
 ProductID, SpecialOfferID, UnitPrice. */
$parameters = array( array(43659, 8, 711, 1, 20.19),
                     array(43660, 6, 762, 1, 419.46),
                     array(43661, 4, 741, 1, 818.70)
                    );

/* Initialize parameter values. */
$orderId = 0;
$qty = 0;
$prodId = 0;
$specialOfferId = 0;
$price = 0.0;

/* Prepare the statement. $params is implicitly bound to $stmt. */
$stmt = sqlsrv_prepare( $conn, $tsql, array( &$orderId,
                                             &$qty,
                                             &$prodId,
                                             &$specialOfferId,
                                             &$price));
if( $stmt === false )
{
     echo "Statement could not be prepared.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Execute a statement for each set of params in $parameters.
Because $params is bound to $stmt, as the values are changed, the
new values are used in the subsequent execution. */
foreach( $parameters as $params)
{
     list($orderId, $qty, $prodId, $specialOfferId, $price) = $params;
     if( sqlsrv_execute($stmt) === false )
     {
          echo "Statement could not be executed.\n";
          die( print_r( sqlsrv_errors(), true));
     }
     else
     {
          /* Verify that the row was successfully inserted. */
          echo "Rows affected: ".sqlsrv_rows_affected( $stmt )."\n";
     }
}

/* Revert the changes */
$d_sql2 = "DELETE FROM Sales.SalesOrderDetail WHERE SalesOrderID=43659 AND OrderQty=8 AND ProductID=711 AND SpecialOfferID=1 AND Unitprice=20.19";
$stmt2 = sqlsrv_query($conn, $d_sql2); 
$d_sql3 = "DELETE FROM Sales.SalesOrderDetail WHERE SalesOrderID=43660 AND OrderQty=6 AND ProductID=762 AND SpecialOfferID=1 AND Unitprice=419.46";
$stmt3 = sqlsrv_query($conn, $d_sql3); 
$d_sql4 = "DELETE FROM Sales.SalesOrderDetail WHERE SalesOrderID=43661 AND OrderQty=4 AND ProductID=741 AND SpecialOfferID=1 AND Unitprice=818.70";
$stmt4 = sqlsrv_query($conn, $d_sql4); 

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_free_stmt( $stmt2);
sqlsrv_free_stmt( $stmt3);
sqlsrv_free_stmt( $stmt4);
sqlsrv_close( $conn);
?>
--EXPECT--
Rows affected: 1
Rows affected: 1
Rows affected: 1