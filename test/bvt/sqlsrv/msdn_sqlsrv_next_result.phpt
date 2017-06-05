--TEST--
first result is consumed without calling next_result, the next result is made available by calling next_result
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

/* Drop the stored procedure if it already exists. */
$tsql_dropSP = "IF OBJECT_ID('InsertProductReview', 'P') IS NOT NULL
                DROP PROCEDURE InsertProductReview";
$stmt1 = sqlsrv_query( $conn, $tsql_dropSP);
if( $stmt1 === false )
{
     echo "Error in executing statement 1.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Create the stored procedure. */
$tsql_createSP = " CREATE PROCEDURE InsertProductReview
                                    @ProductID int,
                                    @ReviewerName nvarchar(50),
                                    @ReviewDate datetime,
                                    @EmailAddress nvarchar(50),
                                    @Rating int,
                                    @Comments nvarchar(3850)
                   AS
                       BEGIN
                             INSERT INTO Production.ProductReview 
                                         (ProductID,
                                          ReviewerName,
                                          ReviewDate,
                                          EmailAddress,
                                          Rating,
                                          Comments)
                                    VALUES
                                         (@ProductID,
                                          @ReviewerName,
                                          @ReviewDate,
                                          @EmailAddress,
                                          @Rating,
                                          @Comments);
                             SELECT * FROM Production.ProductReview
                                WHERE ProductID = @ProductID;
                       END";
$stmt2 = sqlsrv_query( $conn, $tsql_createSP);

if( $stmt2 === false)
{
     echo "Error in executing statement 2.\n";
     die( print_r( sqlsrv_errors(), true));
}
/*-------- The next few steps call the stored procedure. --------*/

/* Define the Transact-SQL query. Use question marks (?) in place of the
parameters to be passed to the stored procedure */
$tsql_callSP = "{call InsertProductReview(?, ?, ?, ?, ?, ?)}";

/* Define the parameter array. */
$productID = 709;
$reviewerName = "Morris Gogh";
$reviewDate = "2008-02-12";
$emailAddress = "customer@email.com";
$rating = 3;
$comments = "[Insert comments here.]";
$params = array( 
                 $productID,
                 $reviewerName,
                 $reviewDate,
                 $emailAddress,
                 $rating,
                 $comments
               );

/* Execute the query. */
$stmt3 = sqlsrv_query( $conn, $tsql_callSP, $params);
if( $stmt3 === false)
{
     echo "Error in executing statement 3.\n";
     die( print_r( sqlsrv_errors(), true));
}

echo "<p>";

/* Consume the first result (rows affected by INSERT query in the
stored procedure) without calling sqlsrv_next_result. */
echo "Rows affectd: ".sqlsrv_rows_affected($stmt3)."-----\n";

echo "<p>";

/* Move to the next result and display results. */
$next_result = sqlsrv_next_result($stmt3);
if( $next_result )
{
	 echo "<p>";
     echo "\nReview information for product ID ".$productID.".---\n";
     while( $row = sqlsrv_fetch_array( $stmt3, SQLSRV_FETCH_ASSOC))
     {
          echo "<br>ReviewerName: ".$row['ReviewerName']."\n";
          echo "<br>ReviewDate: ".date_format($row['ReviewDate'],
                                             "M j, Y")."\n";
          echo "<br>EmailAddress: ".$row['EmailAddress']."\n";
          echo "<br>Rating: ".$row['Rating']."\n\n";
     }
}
elseif( is_null($next_result))
{
     echo "<p>";
	 echo "No more results.\n";
}
else
{
     echo "Error in moving to next result.\n";
     die(print_r(sqlsrv_errors(), true));
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt1 );
sqlsrv_free_stmt( $stmt2 );
sqlsrv_free_stmt( $stmt3 );
sqlsrv_free_stmt( $stmt4 );


sqlsrv_close( $conn );
?>
--EXPECT--
<p>Rows affectd: 1-----
<p><p>
Review information for product ID 709.---
<br>ReviewerName: John Smith
<br>ReviewDate: Sep 18, 2013
<br>EmailAddress: john@fourthcoffee.com
<br>Rating: 5

<br>ReviewerName: Morris Gogh
<br>ReviewDate: Feb 12, 2008
<br>EmailAddress: customer@email.com
<br>Rating: 3