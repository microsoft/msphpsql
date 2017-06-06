<?php
/* Connect to the local server using Windows Authentication and
specify the AdventureWorks database as the database in use. */
$serverName = "sql-2k14-sp1-1.galaxy.ad";
$connectionInfo = array( "Database"=>"AdventureWorks2014", "UID"=>"sa", "PWD"=>"Moonshine4me");
$conn = sqlsrv_connect( $serverName, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Set up the Transact-SQL query. */
$tsql = "SELECT LargePhoto 
         FROM Production.ProductPhoto 
         WHERE ProductPhotoID = ?";

/* Set the parameter values and put them in an array. */
$productPhotoID = 70;
$params = array( $productPhotoID);

/* Execute the query. */
$stmt = sqlsrv_query($conn, $tsql, $params);
if( $stmt === false )
{
     echo "Error in statement execution.</br>";
     die( print_r( sqlsrv_errors(), true));
}

/* Retrieve and display the data.
The return data is retrieved as a binary stream. */
if ( sqlsrv_fetch( $stmt ) )
{
   $image = sqlsrv_get_field( $stmt, 0, 
                      SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_BINARY));
   header("Content-Type: image/jpg");
   fpassthru($image);
}
else
{
     echo "Error in retrieving data.</br>";
     die(print_r( sqlsrv_errors(), true));
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
