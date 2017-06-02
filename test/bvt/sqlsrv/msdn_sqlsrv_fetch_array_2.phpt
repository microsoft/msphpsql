--TEST--
retrieves each row of a result set as a numerically indexed array.
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

/* Define the query. */
$tsql = "SELECT ProductID,
                UnitPrice,
                StockedQty 
         FROM Purchasing.PurchaseOrderDetail
         WHERE StockedQty < 3 
         AND DueDate='2014-01-29'";

/* Execute the query. */
$stmt = sqlsrv_query( $conn, $tsql);
if ( $stmt )
{
     echo "Statement executed.\n";
} 
else 
{
     echo "Error in statement execution.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Iterate through the result set printing a row of data upon each
iteration.*/
while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC))
{
     echo "ProdID: ".$row[0]."\n";
     echo "UnitPrice: ".$row[1]."\n";
     echo "StockedQty: ".$row[2]."\n";
     echo "-----------------\n";
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Statement executed.
ProdID: 931
UnitPrice: 34.3455
StockedQty: .00
-----------------
ProdID: 932
UnitPrice: 39.2385
StockedQty: .00
-----------------