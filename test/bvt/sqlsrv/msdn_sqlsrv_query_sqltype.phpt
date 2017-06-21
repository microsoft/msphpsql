--TEST--
sqlsrv types are specified for the parameters in query.
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
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Define the query. */
$tsql1 = "INSERT INTO HumanResources.EmployeePayHistory (BusinessEntityID,
                                                        RateChangeDate,
                                                        Rate,
                                                        PayFrequency)
           VALUES (?, ?, ?, ?)";

/* Construct the parameter array. */
$businessEntityId = 6;
$changeDate = "2005-06-07";
$rate = 30;
$payFrequency = 2;
$params1 = array(
               array($businessEntityId, null),
               array($changeDate, null, null, SQLSRV_SQLTYPE_DATETIME),
               array($rate, null, null, SQLSRV_SQLTYPE_MONEY),
               array($payFrequency, null, null, SQLSRV_SQLTYPE_TINYINT)
           );

/* Execute the INSERT query. */
$stmt1 = sqlsrv_query($conn, $tsql1, $params1);
if( $stmt1 === false )
{
     echo "Error in execution of INSERT.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Retrieve the newly inserted data. */
/* Define the query. */
$tsql2 = "SELECT BusinessEntityID, RateChangeDate, Rate, PayFrequency
          FROM HumanResources.EmployeePayHistory
          WHERE BusinessEntityID = ? AND RateChangeDate = ?";

/* Construct the parameter array. */
$params2 = array($businessEntityId, $changeDate);

/*Execute the SELECT query. */
$stmt2 = sqlsrv_query($conn, $tsql2, $params2);
if( $stmt2 === false )
{
     echo "Error in execution of SELECT.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Retrieve and display the results. */
$row = sqlsrv_fetch_array( $stmt2 );
if( $row === false )
{
     echo "Error in fetching data.\n";
     die( print_r( sqlsrv_errors(), true));
}
echo "BusinessEntityID: ".$row['BusinessEntityID']."\n";
echo "Change Date: ".date_format($row['RateChangeDate'], "Y-m-d")."\n";
echo "Rate: ".$row['Rate']."\n";
echo "PayFrequency: ".$row['PayFrequency']."\n";

/* Revert the insert */
$d_sql = "delete from HumanResources.EmployeePayHistory where BusinessEntityId=6 and RateChangeDate='2005-06-07 00:00:00.000'";
$stmt = sqlsrv_query($conn, $d_sql);

/* Free statement and connection resources. */
sqlsrv_free_stmt($stmt1);
sqlsrv_free_stmt($stmt2);
sqlsrv_close($conn);
?>
--EXPECT--
BusinessEntityID: 6
Change Date: 2005-06-07
Rate: 30.0000
PayFrequency: 2