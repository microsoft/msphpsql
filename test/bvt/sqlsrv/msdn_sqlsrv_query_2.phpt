--TEST--
Query update a field in a table
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

/* Set up the parameterized query. */
$tsql = "UPDATE Sales.SalesOrderDetail 
         SET OrderQty = ( ?) 
         WHERE SalesOrderDetailID = ( ?)";

/* Assign literal parameter values. */
$params = array( 5, 10);

/* Execute the query. */
if( sqlsrv_query( $conn, $tsql, $params))
{
      echo "Statement executed.\n";
} 
else
{
      echo "Error in statement execution.\n";
      die( print_r( sqlsrv_errors(), true));
}

/*revert the update */
$r_sql = "UPDATE Sales.SalesOrderDetail SET OrderQty=6 WHERE SalesOrderDetailID=10";
$stmt = sqlsrv_query($conn, $r_sql);

/* Free connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Statement executed.