--TEST--
retrieve a row of data.
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

/* Set up and execute the query. Note that both ReviewerName and
Comments are of SQL Server type nvarchar. */
$tsql = "SELECT ReviewerName, Comments 
         FROM Production.ProductReview
         WHERE ProductReviewID=1";
$stmt = sqlsrv_query( $conn, $tsql);
if( $stmt === false )
{
     echo "Error in statement preparation/execution.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Make the first row of the result set available for reading. */
if( sqlsrv_fetch( $stmt ) === false)
{
     echo "Error in retrieving row.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Note: Fields must be accessed in order.
Get the first field of the row. Note that no return type is
specified. Data will be returned as a string, the default for
a field of type nvarchar.*/
$name = sqlsrv_get_field( $stmt, 0);
echo "$name: ";

/*Get the second field of the row as a stream.
Because the default return type for a nvarchar field is a
string, the return type must be specified as a stream. */
$stream = sqlsrv_get_field( $stmt, 1, 
                            SQLSRV_PHPTYPE_STREAM( SQLSRV_ENC_CHAR));
while( !feof( $stream ))
{ 
    $str = fread( $stream, 10000);
    echo $str;
}

/* Free the statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);

?>
--EXPECT--
John Smith: I can't believe I'm singing the praises of a pair of socks, but I just came back from a grueling
3-day ride and these socks really helped make the trip a blast. They're lightweight yet really cushioned my feet all day. 
The reinforced toe is nearly bullet-proof and I didn't experience any problems with rubbing or blisters like I have with
other brands. I know it sounds silly, but it's always the little stuff (like comfortable feet) that makes or breaks a long trip.
I won't go on another trip without them!