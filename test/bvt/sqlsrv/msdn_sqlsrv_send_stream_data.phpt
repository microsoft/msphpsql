--TEST--
Sends data from parameter streams to the server
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

/* Define the query. */
$tsql = "UPDATE Production.ProductReview 
         SET Comments = ( ?) 
         WHERE ProductReviewID = 3";

/* Open parameter data as a stream and put it in the $params array. */
$comment = fopen( "data://text/plain,[ Insert lengthy comment.]", "r");
$params = array( &$comment);

/* Prepare the statement. Use the $options array to turn off the
default behavior, which is to send all stream data at the time of query
execution. */
$options = array("SendStreamParamsAtExec"=>0);
$stmt = sqlsrv_prepare( $conn, $tsql, $params, $options);

/* Execute the statement. */
sqlsrv_execute( $stmt);

/* Send up to 8K of parameter data to the server with each call to
sqlsrv_send_stream_data. Count the calls. */
$i = 1;
while( sqlsrv_send_stream_data( $stmt)) 
{
      echo "$i call(s) made.<br>";
      $i++;
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
1 call(s) made.<br>2 call(s) made.<br>3 call(s) made.<br>