--TEST--
Prepares and executes a statement.
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
     echo "Could not connect.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Set up Transact-SQL query. */
$tsql = "UPDATE Sales.SalesOrderDetail 
         SET OrderQty = ? 
         WHERE SalesOrderDetailID = ?";

/* Assign parameter values. */
$param1 = 5;
$param2 = 10;
$params = array( &$param1, &$param2);

/* Prepare the statement. */
if( $stmt = sqlsrv_prepare( $conn, $tsql, $params))
{
      echo "Statement prepared.<br>";
} 
else
{
      echo "Statement could not be prepared.<br>";
      die( print_r( sqlsrv_errors(), true));
}

/* Execute the statement. */
if( sqlsrv_execute( $stmt))
{
      echo "Statement executed.<br>";
}
else
{
      echo "Statement could not be executed.<br>";
      die( print_r( sqlsrv_errors(), true));
}

/*revert the update */
$r_sql = "UPDATE Sales.SalesOrderDetail SET OrderQty=6 WHERE SalesOrderDetailID=10";
$stmt2 = sqlsrv_query($conn, $r_sql);

/* Free the statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_free_stmt($stmt2);
sqlsrv_close( $conn);
?>
--EXPECT--
Statement prepared.<br>Statement executed.<br>