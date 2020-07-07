--TEST--
Sends data from parameter streams to the server
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
require('connect.inc');
$connectionInfo = array("Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === false) {
    echo "Could not connect.<br>";
    die(print_r(sqlsrv_errors(), true));
}

/* Define the query. */
$tsql = "UPDATE Production.ProductReview 
         SET Comments = (?) 
         WHERE ProductReviewID = 3";
$number = rand(99, 9999);
$input = "[Insert some number $number]";

/* Open parameter data as a stream and put it in the $params array. */
$comments = fopen("data://text/plain,$input", "r");
$params = array(&$comments);

/* Prepare the statement. Use the $options array to turn off the
default behavior, which is to send all stream data at the time of query
execution. */
$options = array("SendStreamParamsAtExec" => 0);
$stmt = sqlsrv_prepare($conn, $tsql, $params, $options);

/* Execute the statement. */
sqlsrv_execute($stmt);

/* Send up to 8K of parameter data to the server with each call to
sqlsrv_send_stream_data. Count the calls. */
$i = 0;
while (sqlsrv_send_stream_data($stmt)) {
    $i++;
}

/* For PHP 7.2, it takes 2 calls whereas older PHP versions
take up to 3 calls */
if ($i < 2 || $i > 3) {
    echo "Expects 2 to 3 calls only." . PHP_EOL;
}

/* Read it back to check the comments */
$tsql = "SELECT Comments FROM Production.ProductReview 
         WHERE ProductReviewID = 3";
$stmt = sqlsrv_query($conn, $tsql);
if (sqlsrv_fetch($stmt)) {
    $review = sqlsrv_get_field($stmt, 0);
    if ($review !== $input) {
        echo "Comments retrieved \'$review\' is incorrect!" . PHP_EOL;
    }
} else {
    echo "Error in retrieving comments!" . PHP_EOL;
    die(print_r(sqlsrv_errors(), true));
}

echo "Done" . PHP_EOL;

/* Free statement and connection resources. */
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECT--
Done