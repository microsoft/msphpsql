--TEST--
retrieves row as a stream specified as a character stream.
--SKIPIF--

--FILE--
<?php
/*Connect to the local server using Windows Authentication and
specify the AdventureWorks database as the database in use. */
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Set up the Transact-SQL query. */
$tsql = "SELECT ReviewerName, 
               CONVERT(varchar(32), ReviewDate, 107) AS [ReviewDate],
               Rating, 
               Comments 
         FROM Production.ProductReview 
         WHERE ProductReviewID = ? ";

/* Set the parameter value. */
$productReviewID = 1;
$params = array( $productReviewID);

/* Execute the query. */
$stmt = sqlsrv_query($conn, $tsql, $params);
if( $stmt === false )
{
     echo "Error in statement execution.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Retrieve and display the data. The first three fields are retrieved
as strings and the fourth as a stream with character encoding. */
if(sqlsrv_fetch( $stmt ) === false )
{
     echo "Error in retrieving row.\n";
     die( print_r( sqlsrv_errors(), true));
}

echo "Name: ".sqlsrv_get_field( $stmt, 0 )."\n";
echo "Date: ".sqlsrv_get_field( $stmt, 1 )."\n";
echo "Rating: ".sqlsrv_get_field( $stmt, 2 )."\n";
echo "Comments: ";
$comments = sqlsrv_get_field( $stmt, 3, 
                             SQLSRV_PHPTYPE_STREAM(SQLSRV_ENC_CHAR));
fpassthru($comments);

/* Free the statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Name: John Smith
Date: Sep 18, 2013
Rating: 5
Comments: I can't believe I'm singing the praises of a pair of socks, but I just came back from a grueling
3-day ride and these socks really helped make the trip a blast. They're lightweight yet really cushioned my feet all day. 
The reinforced toe is nearly bullet-proof and I didn't experience any problems with rubbing or blisters like I have with
other brands. I know it sounds silly, but it's always the little stuff (like comfortable feet) that makes or breaks a long trip.
I won't go on another trip without them!