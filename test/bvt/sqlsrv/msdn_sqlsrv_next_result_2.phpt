--TEST--
executes a batch query that retrieves information, insert an entry, then again retrieves information
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

/* Revert inserts from previous msdn_next_result_2 tests */
$d_sql = "DELETE FROM Production.ProductReview WHERE EmailAddress!='laura@treyresearch.net' AND ProductID=798";
$stmt = sqlsrv_query($conn, $d_sql);

/* Define the batch query. */
$tsql = "--Query 1
         SELECT ProductID, ReviewerName, Rating 
         FROM Production.ProductReview 
         WHERE ProductID=?;

         --Query 2
         INSERT INTO Production.ProductReview (ProductID, 
                                               ReviewerName, 
                                               ReviewDate, 
                                               EmailAddress, 
                                               Rating)
         VALUES (?, ?, ?, ?, ?);

         --Query 3
         SELECT ProductID, ReviewerName, Rating 
         FROM Production.ProductReview 
         WHERE ProductID=?;";

/* Assign parameter values and execute the query. */
$params = array(798, 
                798, 
                'CustomerName', 
                '2008-4-15', 
                'test@customer.com', 
                3, 
                798 );
$stmt = sqlsrv_query($conn, $tsql, $params);
if( $stmt === false )
{
     echo "Error in statement execution.\n";
     die( print_r( sqlsrv_errors(), true));
}


/* Retrieve and display the first result. */
echo "Query 1 result:\n";
while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC ))
{
     print_r($row);
}

echo "<p>";

/* Move to the next result of the batch query. */
sqlsrv_next_result($stmt);

/* Display the result of the second query. */
echo "Query 2 result:\n";
echo "Rows Affected: ".sqlsrv_rows_affected($stmt)."\n";

echo "<p>";

/* Move to the next result of the batch query. */
sqlsrv_next_result($stmt);

/* Retrieve and display the third result. */
echo "Query 3 result:\n";
while( $row = sqlsrv_fetch_array( $stmt, SQLSRV_FETCH_NUMERIC ))
{
     print_r($row);
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt );
sqlsrv_close( $conn );
?>
--EXPECT--
Query 1 result:
Array
(
    [0] => 798
    [1] => Laura Norman
    [2] => 5
)
<p>Query 2 result:
Rows Affected: 1
<p>Query 3 result:
Array
(
    [0] => 798
    [1] => Laura Norman
    [2] => 5
)
Array
(
    [0] => 798
    [1] => CustomerName
    [2] => 3
)
