--TEST--
insert stream.
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
/* Connect to the local server using Windows Authentication and
specify the AdventureWorks database as the database in use. */
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect($server, $connectionInfo);
if ($conn === false) {
    echo "Could not connect.\n";
    die(print_r(sqlsrv_errors(), true));
}

/* Remove any records with from the table with ProductID = 999*/
$productID = 999;
$tsql = "DELETE FROM Production.ProductReview WHERE ProductID = $productID";
sqlsrv_query($conn, $tsql);

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

$number = rand(99, 9999);
$input = "[Insert some number $number]";

/* There is no record in this table with ProductID = 999 */
$name = 'Customer Name';
$date = date("Y-m-d");
$email = 'customer@name.com';
$rating = 3;
$comments = fopen("data://text/plain,$input", "r");
$params = array($productID, $name, $date, $email, $rating, $comments);

/* Execute the query. All stream data is sent upon execution.*/
$stmt = sqlsrv_query($conn, $tsql, $params);
if ($stmt === false) {
    echo "Error in statement execution.\n";
    die(print_r(sqlsrv_errors(), true));
}

/* Read it back to check the comment */
$tsql = "SELECT Comments FROM Production.ProductReview 
         WHERE ProductID = $productID";
$stmt = sqlsrv_query($conn, $tsql);
if (sqlsrv_fetch($stmt)) {
    $review = sqlsrv_get_field($stmt, 0);
    if ($review !== $input) {
        echo "Comment retrieved \'$review\' is incorrect!" . PHP_EOL;
    }
} else {
    echo "Error in retrieving comments!" . PHP_EOL;
    die(print_r(sqlsrv_errors(), true));
}

/* Remove the entry from the table */
$tsql = "DELETE FROM Production.ProductReview WHERE ProductID = $productID";
sqlsrv_query($conn, $tsql);

echo "Done" . PHP_EOL;

/* Free statement and connection resources. */
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECT--
Done
