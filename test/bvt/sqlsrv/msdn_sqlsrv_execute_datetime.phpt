--TEST--
execute with datetime type in bind parameters.
--SKIPIF--

?>
--FILE--
<?php
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false)
{
     echo "Could not connect.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Prepare with datetime type in parameter. */
$tsql = "SELECT * FROM Sales.CurrencyRate WHERE CurrencyRateDate=(?)";

//Pass in parameters directly
$params = array( '2014-05-31 00:00:00.000');
$stmt = sqlsrv_prepare( $conn, $tsql, $params);

//Pass in parameters through reference
$crd='2014-05-31 00:00:00.000';
$stmt = sqlsrv_prepare( $conn, $tsql, array(&$crd));

echo "Datetime Type, Select Query:<br>";
if( $stmt )
{
     echo "Statement prepared.<br>";
}
else
{
     echo "Error in preparing statement.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Execute the statement. Display any errors that occur. */
if( sqlsrv_execute( $stmt))
{
      echo "Statement executed.<br>";
}
else
{
     echo "Error in executing statement.<br>";
     die( print_r( sqlsrv_errors(), true));
}
while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC))
{
      echo $row['CurrencyRateID'].", ".date_format($row['CurrencyRateDate'], 'Y-m-d H:i:s')."<br>";
}
echo "<br>";

sqlsrv_free_stmt( $stmt);


/* Prepare with datetime type in parameter. */
$tsql = "UPDATE Sales.CurrencyRate SET FromCurrencyCode='CAD' WHERE CurrencyRateDate=(?)";

//Pass in parameters directly
$params = array( '2011-08-15 00:00:00.000');
$stmt = sqlsrv_prepare( $conn, $tsql, $params);

//Pass in parameters through reference
$crd='2011-08-15 00:00:00.000';
$stmt = sqlsrv_prepare( $conn, $tsql, array(&$crd));

echo "Datetime Type, Update Query:<br>";
if( $stmt )
{
     echo "Statement prepared.<br>";
}
else
{
     echo "Error in preparing statement.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Execute the statement. Display any errors that occur. */
if( sqlsrv_execute( $stmt))
{
      echo "Statement executed.<br>";
}
else
{
     echo "Error in executing statement.<br>";
     die( print_r( sqlsrv_errors(), true));
}
echo sqlsrv_rows_affected( $stmt)." rows affected.<br><br>";
sqlsrv_free_stmt( $stmt);


/* Revert the Update*/
$tsql = "UPDATE Sales.CurrencyRate SET FromCurrencyCode='USD' WHERE CurrencyRateDate=(?)";

//Pass in parameters directly
$params = array( '2011-08-15 00:00:00.000');
$stmt = sqlsrv_prepare( $conn, $tsql, $params);

//Pass in parameters through reference
$crd='2011-08-15 00:00:00.000';
$stmt = sqlsrv_prepare( $conn, $tsql, array(&$crd));

echo "Datetime Type, Update Query:<br>";
if( $stmt )
{
     echo "Statement prepared.<br>";
}
else
{
     echo "Error in preparing statement.<br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Execute the statement. Display any errors that occur. */
if( sqlsrv_execute( $stmt))
{
      echo "Statement executed.<br>";
}
else
{
     echo "Error in executing statement.<br>";
     die( print_r( sqlsrv_errors(), true));
}
echo sqlsrv_rows_affected( $stmt)." rows affected.<br>";


/* Free the statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Datetime Type, Select Query:<br>Statement prepared.<br>Statement executed.<br>12425, 2014-05-31 00:00:00<br>12426, 2014-05-31 00:00:00<br>12427, 2014-05-31 00:00:00<br>12428, 2014-05-31 00:00:00<br>12429, 2014-05-31 00:00:00<br>12430, 2014-05-31 00:00:00<br>12431, 2014-05-31 00:00:00<br>12432, 2014-05-31 00:00:00<br>12433, 2014-05-31 00:00:00<br>12434, 2014-05-31 00:00:00<br>13532, 2014-05-31 00:00:00<br>12435, 2014-05-31 00:00:00<br><br>Datetime Type, Update Query:<br>Statement prepared.<br>Statement executed.<br>14 rows affected.<br><br>Datetime Type, Update Query:<br>Statement prepared.<br>Statement executed.<br>14 rows affected.<br>