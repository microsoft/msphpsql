--TEST--
num_rows with a ekyset cursor should work.
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

$stmt = sqlsrv_query( $conn, "select * from Sales.SalesOrderHeader where CustomerID = 29565" , array(), array( "Scrollable" => SQLSRV_CURSOR_KEYSET ));

   $row_count = sqlsrv_num_rows( $stmt );

   if ($row_count === false)
      echo "\nerror\n";
   else if ($row_count >=0)
      echo "\n$row_count\n";
?>
--EXPECT--

8