--TEST--
sqlsrv_fetch_object() into a class with Unicode column name
--SKIPIF--
--FILE--
<?php

/* Define the Product class. */  
class Product
{
	public function __construct($ID,$UID)  
	{  
		$this->objID = $ID;  
		$this->name = $UID;  
	}  
	public $objID;  
	public $name;  
	public $StockedQty;  
	public $SafetyStockLevel;
	public $Code;  
	private $UnitPrice;  
	function getPrice()  
	{  
		return $this->UnitPrice." [CAD]";  
	}  

	public function report_output()
	{
		echo "Object ID: ".$this->objID."\n";  
		echo "Internal Name: ".$this->name."\n";  
		echo "Product Name: ".$this->личное_имя."\n";  
		echo "Stocked Qty: ".$this->StockedQty."\n";  
		echo "Safety Stock Level: ".$this->SafetyStockLevel."\n";  
		echo "Color: ".$this->Color."\n";  
		echo "Country: ".$this->Code."\n";  
		echo "Unit Price: ".$this->getPrice()."\n";  
    } 
}  

class Sample extends Product
{
     public function __construct($ID)  
     {  
          $this->objID = $ID;  
     }
	 
     function getPrice()  
     {  
          return $this->UnitPrice ." [EUR]";  
     } 
     
     public function report_output()
     {
     echo "ID: ".$this->objID."\n";  
     echo "Name: ".$this->личное_имя."\n";
     echo "Unit Price: ".$this->getPrice()."\n";  
     }
}


include 'MsCommon.inc';
$tableName = "UnicodeColNameTest";

include 'MsSetup.inc';

$conn = ConnectUTF8();

$tableName = "UnicodeColNameTest";



// Create table Purchasing
$tableName1 = "Purchasing";
$tableName2 = "Country";
DropTable($conn, $tableName1);
DropTable($conn, $tableName2);
$sql = "create table $tableName1 (ID CHAR(4), личное_имя VARCHAR(128), SafetyStockLevel SMALLINT, 
	StockedQty INT, UnitPrice FLOAT, DueDate datetime, Color VARCHAR(20))";
sqlsrv_query($conn, $sql) ?: die( print_r( sqlsrv_errors(), true ));

// Insert data
$sql = "INSERT INTO $tableName1 VALUES
	('P001','Pencil 2B','102','24','0.24','2016-02-01','Red'),
	('P002','Notepad','102','12','3.87', '2016-02-21',Null),
	('P001','Mirror 2\"','652','3','15.99', '2016-02-01',NULL),
	('P003','USB connector','1652','31','9.99','2016-02-01',NULL)";
sqlsrv_query( $conn, $sql) ?: die( print_r( sqlsrv_errors(), true ));

// Create table Country
$sql = "create table $tableName2 (SerialNumber CHAR(4), Code VARCHAR(2))";
sqlsrv_query($conn, $sql) ?: die( print_r( sqlsrv_errors(), true ));

// Insert data
$sql = "INSERT INTO $tableName2 VALUES ('P001','FR'),('P002','UK'),('P003','DE')";
sqlsrv_query( $conn, $sql) ?: die( print_r( sqlsrv_errors(), true ));
  
/* Define the query. */
$sql = "SELECT личное_имя, SafetyStockLevel, StockedQty, UnitPrice, Color, Code 
         FROM $tableName1 AS Purchasing
         JOIN $tableName2 AS Country
         ON Purchasing.ID = Country.SerialNumber   
         WHERE Purchasing.StockedQty < ?
         AND Purchasing.UnitPrice < ?
         AND Purchasing.DueDate= ?";  
  
/* Set the parameter values. */  
$params = array(100, '10.5', '2016-02-01');  
  
/* Execute the query. */
$stmt = sqlsrv_query( $conn, $sql, $params, array("Scrollable"=>"static")); //, array("Scrollable"=>"buffered")  
if (!$stmt)
{
     echo "Error in statement execution.\n";
     die( print_r( sqlsrv_errors(), true));
}

// Iterate through the result set.  
// $product is an instance of the Product class.  
$i=0; $hasNext = TRUE; 

while( $hasNext )  
{  
 	$sample = sqlsrv_fetch_object( $stmt, "Sample", array($i+1000),SQLSRV_SCROLL_ABSOLUTE,$i);
	
	// DEBUG: uncomment to see the SQL_SERVER ERROR
	// if(!$sample) die( print_r( sqlsrv_errors(), true));
	
	if(!$sample) {
	 	$hasNext = false;
	}
	else {
		$sample->report_output(); 
		$i++;
	}
}  

// DROP database
// $stmt = sqlsrv_query($conn,"DROP DATABASE ". $dbName);
 //echo $dbName;
 DropTable($conn, $tableName1);
 DropTable($conn, $tableName2);
// Free statement and connection resources.s
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);  

print "Done";
?>

--EXPECT--
ID: 1000
Name: Pencil 2B
Unit Price: 0.24 [EUR]
ID: 1001
Name: USB connector
Unit Price: 9.99 [EUR]
Done
