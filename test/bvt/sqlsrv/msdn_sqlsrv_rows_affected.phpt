--TEST--
Returns the number of rows modified by the last statement executed.
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

/* Set up Transact-SQL query. */
$tsql = "UPDATE Sales.SalesOrderDetail 
         SET SpecialOfferID = ? 
         WHERE ProductID = ?";

/* Set parameter values. */
$params = array(2, 709);

/* Execute the statement. */
$stmt = sqlsrv_query( $conn, $tsql, $params);

/* Get the number of rows affected and display appropriate message.*/
$rows_affected = sqlsrv_rows_affected( $stmt);
if( $rows_affected === false)
{
     echo "Error in calling sqlsrv_rows_affected.\n";
     die( print_r( sqlsrv_errors(), true));
}
elseif( $rows_affected == -1)
{
      echo "No information available.\n";
}
else
{
      echo $rows_affected." rows were updated.\n";
}

/*revert the update */
$r_sql2 = "UPDATE Sales.SalesOrderDetail SET SpecialOfferID=1 WHERE ProductID=709 AND UnitPriceDiscount=0.00";
$stmt2 = sqlsrv_query($conn, $r_sql2);
$r_sql3 = "UPDATE Sales.SalesOrderDetail SET SpecialOfferID=3 WHERE ProductID=709 AND UnitPriceDiscount=0.05";
$stmt3 = sqlsrv_query($conn, $r_sql3);
$r_sql4 = "UPDATE Sales.SalesOrderDetail SET SpecialOfferID=4 WHERE ProductID=709 AND UnitPriceDiscount=0.10";
$stmt4 = sqlsrv_query($conn, $r_sql4);

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_free_stmt( $stmt2);
sqlsrv_free_stmt( $stmt3);
sqlsrv_free_stmt( $stmt4);
sqlsrv_close( $conn);
?>
--EXPECTREGEX--
[0-9]+ rows were updated.