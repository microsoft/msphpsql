--TEST--
retrieves each row of a result set as an instance of the Product class defined in the script.
--SKIPIF--

--FILE--
<?php
/* Define the Product class. */
class Product
{
     /* Constructor */
     public function ProductConstruct($ID)
     {
          $this->objID = $ID;
     }
     public $objID;
     public $name;
     public $StockedQty;
     public $SafetyStockLevel;
     private $UnitPrice;
     function getPrice()
     {
          return $this->UnitPrice;
     }
}

/* Connect to the local server using Windows Authentication, and
specify the AdventureWorks database as the database in use. */
require('connect.inc');
$connectionInfo = array( "Database"=>"$databaseName", "UID"=>"$uid", "PWD"=>"$pwd");
$conn = sqlsrv_connect( $server, $connectionInfo);
if( $conn === false )
{
     echo "Could not connect.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Define the query. */
$tsql = "SELECT Name,
                SafetyStockLevel,
                StockedQty,
                UnitPrice,
                Color
         FROM Purchasing.PurchaseOrderDetail AS pdo
         JOIN Production.Product AS p
         ON pdo.ProductID = p.ProductID
         WHERE pdo.StockedQty < ?
         AND pdo.DueDate= ?";

/* Set the parameter values. */
$params = array(3, '2014-01-29');

/* Execute the query. */
$stmt = sqlsrv_query( $conn, $tsql, $params);
if ( $stmt )
{
     echo "Statement executed.\n";
} 
else 
{
     echo "Error in statement execution.\n";
     die( print_r( sqlsrv_errors(), true));
}

/* Iterate through the result set, printing a row of data upon each
 iteration. Note the following:
     1) $product is an instance of the Product class.
     2) The $ctorParams parameter is required in the call to
        sqlsrv_fetch_object, because the Product class constructor is
        explicity defined and requires parameter values.
     3) The "Name" property is added to the $product instance because
        the existing "name" property does not match.
     4) The "Color" property is added to the $product instance
        because there is no matching property.
     5) The private property "UnitPrice" is populated with the value
        of the "UnitPrice" field.*/
$i=0; //Used as the $objID in the Product class constructor.
while( $product = sqlsrv_fetch_object( $stmt, "Product", array($i)))
{
     echo "Object ID: ".$product->objID."\n";
     echo "Product Name: ".$product->Name."\n";
     echo "Stocked Qty: ".$product->StockedQty."\n";
     echo "Safety Stock Level: ".$product->SafetyStockLevel."\n";
     echo "Product Color: ".$product->Color."\n";
     echo "Unit Price: ".$product->getPrice()."\n";
     echo "-----------------\n";
     $i++;
}

/* Free statement and connection resources. */
sqlsrv_free_stmt( $stmt);
sqlsrv_close( $conn);
?>
--EXPECT--
Statement executed.
Object ID: 
Product Name: LL Road Tire
Stocked Qty: .00
Safety Stock Level: 500
Product Color: 
Unit Price: 34.3455
-----------------
Object ID: 
Product Name: ML Road Tire
Stocked Qty: .00
Safety Stock Level: 500
Product Color: 
Unit Price: 39.2385
-----------------