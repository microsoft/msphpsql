--TEST--
retrieve all fields
--SKIPIF--

--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Define and execute the query. */
$tsql = "SELECT TOP (3) * FROM HumanResources.Department";
$stmt = sqlsrv_query($conn, $tsql);
if( $stmt === false)
{
     echo "Error in executing query.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Retrieve the number of fields. */
$numFields = sqlsrv_num_fields( $stmt );

/* Iterate through each row of the result set. */
while( sqlsrv_fetch( $stmt ))
{
     /* Iterate through the fields of each row. */
     for($i = 0; $i < $numFields; $i++)
     {
          echo sqlsrv_get_field($stmt, $i, 
                   SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR))." ";
     }
     echo "<br>";
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );
?>
--EXPECT--
1 Engineering Research and Development 2008-04-30 00:00:00.000 <br>2 Tool Design Research and Development 2008-04-30 00:00:00.000 <br>3 Sales Sales and Marketing 2008-04-30 00:00:00.000 <br>