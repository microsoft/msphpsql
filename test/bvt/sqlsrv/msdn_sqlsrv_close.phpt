--TEST--
closes a connection.
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
$tsql = "SELECT OrderQty, UnitPrice FROM Sales.SalesOrderDetail";
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

/* Close the connection. */
sqlsrv_close( $conn);
echo "Connection closed.\n";
?>
--EXPECT--
Connection closed.