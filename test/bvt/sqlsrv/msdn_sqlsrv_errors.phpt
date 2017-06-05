--TEST--
displays errors that occur during a failed statement execution
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

/* Set up a query to select an invalid column name. */
$tsql = "SELECT InvalidColumnName FROM Sales.SalesOrderDetail";

/* Attempt execution. */
/* Execution will fail because of the invalid column name. */
$stmt = sqlsrv_query( $conn, $tsql);
if( $stmt === false )
{
      if( ($errors = sqlsrv_errors() ) != null)
      {
         foreach( $errors as $error)
         {
            echo "SQLSTATE: ".$error[ 'SQLSTATE']."<br>";
            echo "code: ".$error[ 'code']."<br>";
            echo "message: ".$error[ 'message']."<br>";
         }
      }
}

/* Free connection resources */
sqlsrv_close( $conn);
?>
--EXPECTREGEX--
SQLSTATE: 42S22<br>code: 207<br>message: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid column name 'InvalidColumnName'.<br>