--TEST--
retrieves each row of a result set as an associative array.
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

/* Set up and execute the query. */
$tsql = "SELECT FirstName, LastName
         FROM Person.Person
         WHERE LastName='Alan'";
$stmt = sqlsrv_query( $conn, $tsql);
if( $stmt === false)
{
     echo "Error in query preparation/execution.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Retrieve each row as an associative array and display the results.*/
while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC))
{
      echo $row['LastName'].", ".$row['FirstName']."<br>";
}

/* Free statement and connection resources. */

sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Alan, Alisha<br>Alan, Bob<br>Alan, Cheryl<br>Alan, Jamie<br>Alan, Kari<br>Alan, Kelvin<br>Alan, Meghan<br>Alan, Stanley<br>Alan, Xavier<br>