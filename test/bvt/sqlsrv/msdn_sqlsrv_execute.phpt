--TEST--
executes a statement that updates a field.
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false)
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}


/* Set up the Transact-SQL query. */
$tsql = "UPDATE Sales.SalesOrderDetail 
         SET OrderQty = (?) 
         WHERE SalesOrderDetailID = (?)";

/* Set up the parameters array. Parameters correspond, in order, to
question marks in $tsql. */
$params = array(5, 10);



/* Create the statement. */
$stmt = sqlsrv_prepare( $conn, $tsql, $params);
if( $stmt )
{
     echo "Statement prepared.\n";
}
else
{
     echo "Error in preparing statement.\n";
     die( print_r( sqlsrv_errors(), true));
}


/* Execute the statement. Display any errors that occur. */
if( sqlsrv_execute( $stmt))
{
      echo "Statement executed.\n";
}
else
{
     echo "Error in executing statement.\n";
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
Statement prepared.
Statement executed.