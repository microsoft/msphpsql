--TEST--
sned stream data with SendStreamParamsAtExec turned off.
--SKIPIF--

?>
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

/* Set up the Transact-SQL query. */
$tsql = "INSERT INTO Production.ProductReview (ProductID, 
                                               ReviewerName,
                                               ReviewDate,
                                               EmailAddress,
                                               Rating,
                                               Comments)
         VALUES (?, ?, ?, ?, ?, ?)";

/* Set the parameter values and put them in an array.
Note that $comments is opened as a stream. */
$productID = '709';
$name = 'Customer Name';
$date = date("Y-m-d");
$email = 'customer@name.com';
$rating = 3;
$comments = fopen( "data://text/plain,[ Insert lengthy comment here.]",
                  "r");
$params = array($productID, $name, $date, $email, $rating, $comments);

/* Turn off the default behavior of sending all stream data at
execution. */
$options = array("SendStreamParamsAtExec" => 0);

/* Execute the query. */
$stmt = sqlsrv_query($conn, $tsql, $params, $options);
if( $stmt === false )
{
     echo "Error in statement execution.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Send up to 8K of parameter data to the server with each call to
sqlsrv_send_stream_data. Count the calls. */
$i = 1;
while( sqlsrv_send_stream_data( $stmt)) 
{
     echo "$i call(s) made.\n";
     $i++;
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
1 call(s) made.
2 call(s) made.
3 call(s) made.