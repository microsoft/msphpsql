--TEST--
Query insert into a table
--SKIPIF--
<?php require('skipif.inc'); ?>
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

// RevisionNumber in SalesOrderHeader is subject to a trigger incrementing it whenever
// changes are made to SalesOrderDetail. Since RevisonNumber is a tinyint, it can
// overflow quickly if this test is often run. So disable the trigger.
$tsql = "DISABLE TRIGGER uSalesOrderHeader ON Sales.SalesOrderHeader";
$stmt = sqlsrv_query($conn, $tsql);

/* Set up the parameterized query. */
$tsql = "INSERT INTO Sales.SalesOrderDetail 
        (SalesOrderID, 
         OrderQty, 
         ProductID, 
         SpecialOfferID, 
         UnitPrice, 
         UnitPriceDiscount)
        VALUES 
        (?, ?, ?, ?, ?, ?)";

/* Set parameter values. */
$params = array(75123, 5, 741, 1, 818.70, 0.00);

/* Prepare and execute the query. */
$stmt = sqlsrv_query( $conn, $tsql, $params);
if( $stmt )
{
     echo "Row successfully inserted.\n";
}
else
{
     echo "Row insertion failed.\n";
     die( print_r( sqlsrv_errors(), true));
}

// Re-enable the trigger
$tsql = "ENABLE TRIGGER uSalesOrderHeader ON Sales.SalesOrderHeader";
$stmt = sqlsrv_query($conn, $tsql);

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Row successfully inserted.