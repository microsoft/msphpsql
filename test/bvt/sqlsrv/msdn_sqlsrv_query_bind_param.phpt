--TEST--
updates the quantity in a table, the quantity and product ID are parameters in the UPDATE query.
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

/* Define the Transact-SQL query.
Use question marks as parameter placeholders. */
$tsql1 = "UPDATE Production.ProductInventory 
          SET Quantity = ? 
          WHERE ProductID = ?";

/* Initialize $qty and $productId */
$qty = 10; $productId = 709;

/* Execute the statement with the specified parameter values. */
$stmt1 = sqlsrv_query( $conn, $tsql1, array($qty, $productId));
if( $stmt1 === false )
{
     echo "Statement 1 could not be executed.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Free statement resources. */
sqlsrv_free_stmt( $stmt1);

/* Now verify the updated quantity.
Use a question mark as parameter placeholder. */
$tsql2 = "SELECT Quantity 
          FROM Production.ProductInventory
          WHERE ProductID = ?";

/* Execute the statement with the specified parameter value.
Display the returned data if no errors occur. */
$stmt2 = sqlsrv_query( $conn, $tsql2, array($productId));
if( $stmt2 === false )
{
     echo "Statement 2 could not be executed.\n";
     die( print_r(sqlsrv_errors(), true));
}
else
{
     $qty = sqlsrv_fetch_array( $stmt2);
     echo "There are $qty[0] of product $productId in inventory.\n";
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt2);
sqlsrv_close( $conn);
?>
--EXPECT--
There are 10 of product 709 in inventory.