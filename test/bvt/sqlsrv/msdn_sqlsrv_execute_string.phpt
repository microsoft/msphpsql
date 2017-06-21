--TEST--
execute with string type in bind parameters.
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

/* Prepare with string type in parameter. */
$tsql = "SELECT * FROM Sales.SalesOrderDetail WHERE CarrierTrackingNumber=(?)";

//Pass in parameters directly
$params = array( '8650-4A20-B1');
$stmt = sqlsrv_prepare( $conn, $tsql, $params);

//Pass in parameters through reference
//$ctn="8650-4A20-B1";
//$stmt = sqlsrv_prepare( $conn, $tsql, array(&$ctn));

echo "String Type, Select Query:<br>";
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

$soID = 0;
$row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC);
while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_ASSOC))
{
      echo $row['SalesOrderID'].", ".$row['CarrierTrackingNumber']."<br>";
      $soID = $row['SalesOrderID'];
}
echo "<br>";

sqlsrv_free_stmt( $stmt);

/* Prepare with string type in parameter. */
$tsql = "UPDATE Sales.SalesOrderDetail
	SET OrderQty=(?)
	WHERE CarrierTrackingNumber=(?)";

// RevisionNumber in SalesOrderHeader is subject to a trigger incrementing it whenever
// changes are made to SalesOrderDetail. Since RevisonNumber is a tinyint, it can
// overflow quickly if this test is often run. So we change it directly here first
// before it can overflow.
$stmt0 = sqlsrv_query( $conn, "UPDATE Sales.SalesOrderHeader SET RevisionNumber = 2 WHERE SalesOrderID = $soID" );

//Pass in parameters directly
$params = array(5, '8650-4A20-B1');
$stmt = sqlsrv_prepare( $conn, $tsql, $params);

//Pass in parameters through reference
//$oq=5;
//$ctn="8650-4A20-B1";
//$stmt = sqlsrv_prepare( $conn, $tsql, array(&$oq, &$ctn));

echo "String Type, Update Query:<br>";
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


/* Revert back the Update. */
$tsql = "UPDATE Sales.SalesOrderDetail
	SET OrderQty=(?)
	WHERE CarrierTrackingNumber=(?)";

//Pass in parameters directly
$params = array(1, '8650-4A20-B1');
$stmt = sqlsrv_prepare( $conn, $tsql, $params);

//Pass in parameters through reference
//$oq=1;
//$ctn="8650-4A20-B1";
//$stmt = sqlsrv_prepare( $conn, $tsql, array(&$oq, &$ctn));

echo "String Type, Update Query:<br>";
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
sqlsrv_free_stmt( $stmt);


/* Free the statement and connection resources. */
//sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
String Type, Select Query:<br>Statement prepared.<br>Statement executed.<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br>51108, 8650-4A20-B1<br><br>String Type, Update Query:<br>Statement prepared.<br>Statement executed.<br>52 rows affected.<br><br>String Type, Update Query:<br>Statement prepared.<br>Statement executed.<br>52 rows affected.<br>