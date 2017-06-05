--TEST--
retrieves datatime as string and nvarchar as stream.
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

/*revert inserts from previous tests*/
$d_sql = "DELETE FROM Production.ProductReview WHERE EmailAddress!='john@fourthcoffee.com' AND ProductID=709";
$stmt4 = sqlsrv_query($conn, $d_sql);

/* Set up and execute the query. Note that both ReviewerName and
	Comments are of the SQL Server nvarchar type. */
	$tsql = "SELECT ReviewerName, 
                ReviewDate,
                Rating, 
                Comments 
         FROM Production.ProductReview 
         WHERE ProductID = ? 
         ORDER BY ReviewDate DESC";

/* Set the parameter value. */
$productID = 709;
$params = array( $productID);

/* Execute the query. */
$stmt = sqlsrv_query($conn, $tsql, $params);
if( $stmt === false )
{
     echo "Error in statement execution.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Retrieve and display the data. The first and third fields are
retrieved according to their default types, strings. The second field
is retrieved as a string with 8-bit character encoding. The fourth
field is retrieved as a stream with 8-bit character encoding.*/
while ( sqlsrv_fetch( $stmt))
{
   echo "Name: ".sqlsrv_get_field( $stmt, 0 )."\n";
   echo "Date: ".sqlsrv_get_field( $stmt, 1, 
                       SQLSRV_PHPTYPE_STRING( SQLSRV_ENC_CHAR))."\n";
   echo "Rating: ".sqlsrv_get_field( $stmt, 2 )."\n";
   echo "Comments: ";
   $comments = sqlsrv_get_field( $stmt, 3, 
                            SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
   fpassthru( $comments);
   echo "\n"; 
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close($conn);
?>
--EXPECT--
Name: John Smith
Date: 2013-09-18 00:00:00.000
Rating: 5
Comments: I can't believe I'm singing the praises of a pair of socks, but I just came back from a grueling
3-day ride and these socks really helped make the trip a blast. They're lightweight yet really cushioned my feet all day. 
The reinforced toe is nearly bullet-proof and I didn't experience any problems with rubbing or blisters like I have with
other brands. I know it sounds silly, but it's always the little stuff (like comfortable feet) that makes or breaks a long trip.
I won't go on another trip without them!