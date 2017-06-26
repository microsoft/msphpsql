--TEST--
specify the UTF-8 character set when querying
--SKIPIF--

--FILE--
<?php

// Connect to the local server using Windows Authentication and
// specify the AdventureWorks database as the database in use. 
// 
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if ( $conn === false ) {
   echo "Could not connect.<br>";
   die( print_r( sqlsrv_errors(), true));
}

// Set up the Transact-SQL query.
// 
$tsql1 = "UPDATE Production.ProductReview
          SET Comments = ?
          WHERE ProductReviewID = ?";

// Set the parameter values and put them in an array. Note that
// $comments is converted to UTF-8 encoding with the PHP function
// utf8_encode to simulate an application that uses UTF-8 encoded data. 
// 
$reviewID = 3;
$comments = utf8_encode("testing");
$params1 = array(
                  array($comments,
                        SQLSRV_PARAM_IN,
                        SQLSRV_PHPTYPE_STRING('UTF-8')
                  ),
                  array($reviewID)
                );

// Execute the query.
// 
$stmt1 = sqlsrv_query($conn, $tsql1, $params1);

if ( $stmt1 === false ) {
   echo "Error in statement execution.<br>";
   die( print_r( sqlsrv_errors(), true));
}
else {
   echo "The update was successfully executed.<br>";
}

// Retrieve the newly updated data.
// 
$tsql2 = "SELECT Comments 
          FROM Production.ProductReview 
          WHERE ProductReviewID = ?";

// Set up the parameter array.
// 
$params2 = array($reviewID);

// Execute the query.
// 
$stmt2 = sqlsrv_query($conn, $tsql2, $params2);
if ( $stmt2 === false ) {
   echo "Error in statement execution.<br>";
   die( print_r( sqlsrv_errors(), true));
}

// Retrieve and display the data. 
// 
if ( sqlsrv_fetch($stmt2) ) {
   echo "Comments: ";
   $data = sqlsrv_get_field($stmt2, 
                            0, 
                            SQLSRV_PHPTYPE_STRING('UTF-8')
                           );
   echo $data."<br>";
}
else {
   echo "Error in fetching data.<br>";
   die( print_r( sqlsrv_errors(), true));
}

// Free statement and connection resources.
// 
sqlsrv_free_stmt( $stmt1 );
sqlsrv_free_stmt( $stmt2 );
sqlsrv_close( $conn);
?>
--EXPECT--
The update was successfully executed.<br>Comments: testing<br>