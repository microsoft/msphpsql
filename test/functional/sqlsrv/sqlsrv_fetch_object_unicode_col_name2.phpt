--TEST--
sqlsrv_fetch_object() into a class with Unicode column name
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

// Define the Product class
class Product
{
    public function __construct($ID, $UID)
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
    public function getPrice()
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

    public function getPrice()
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

function getInputData1($inputs)
{
    return array('ID' => $inputs[0],
                 'личное_имя'=> $inputs[1],
                 'SafetyStockLevel' => $inputs[2],
                 'StockedQty' => $inputs[3],
                 'UnitPrice' => $inputs[4],
                 'DueDate' => $inputs[5],
                 'Color' => $inputs[6]);
}

function getInputData2($inputs)
{
    return array('SerialNumber' => $inputs[0],
                 'Code'=> $inputs[1]);
}

require_once('MsCommon.inc');
$conn = AE\connect(array('CharacterSet'=>'UTF-8'));

// Create table Purchasing
$tableName1 = "Purchasing";
$tableName2 = "Country";

$columns = array(new AE\ColumnMeta('CHAR(4)', 'ID'),
                 new AE\ColumnMeta('VARCHAR(128)', 'личное_имя'),
                 new AE\ColumnMeta('SMALLINT', 'SafetyStockLevel'),
                 new AE\ColumnMeta('INT', 'StockedQty'),
                 new AE\ColumnMeta('FLOAT', 'UnitPrice'),
                 new AE\ColumnMeta('datetime', 'DueDate'),
                 new AE\ColumnMeta('VARCHAR(20)', 'Color'));
AE\createTable($conn, $tableName1, $columns);

// Insert data
if (AE\isColEncrypted()) {
    $sql = "INSERT INTO $tableName1 VALUES
                    (?, ?, ?, ?, ?, ?, ?), 
                    (?, ?, ?, ?, ?, ?, ?), 
                    (?, ?, ?, ?, ?, ?, ?), 
                    (?, ?, ?, ?, ?, ?, ?)";
    $stmt = sqlsrv_query($conn, $sql, array(array('P001', null, null, SQLSRV_SQLTYPE_CHAR(4)), 
                                            array('Pencil 2B', null, null, SQLSRV_SQLTYPE_VARCHAR(128)), 
                                            array('102', null, null, SQLSRV_SQLTYPE_SMALLINT), 
                                            array('24', null, null, SQLSRV_SQLTYPE_INT), 
                                            array('0.24', null, null, SQLSRV_SQLTYPE_FLOAT), 
                                            array('2016-02-01', null, null, SQLSRV_SQLTYPE_DATETIME), 
                                            array('Red', null, null, SQLSRV_SQLTYPE_VARCHAR(20)), 
                                            array('P002', null, null, SQLSRV_SQLTYPE_CHAR(4)), 
                                            array('Notepad', null, null, SQLSRV_SQLTYPE_VARCHAR(128)), 
                                            array('102', null, null, SQLSRV_SQLTYPE_SMALLINT), 
                                            array('12', null, null, SQLSRV_SQLTYPE_INT), 
                                            array('3.87', null, null, SQLSRV_SQLTYPE_FLOAT), 
                                            array('2016-02-21', null, null, SQLSRV_SQLTYPE_DATETIME), 
                                            array(null, null, null, SQLSRV_SQLTYPE_VARCHAR(20)), 
                                            array('P001', null, null, SQLSRV_SQLTYPE_CHAR(4)), 
                                            array('Mirror 2\"', null, null, SQLSRV_SQLTYPE_VARCHAR(128)), 
                                            array('652', null, null, SQLSRV_SQLTYPE_SMALLINT), 
                                            array('3', null, null, SQLSRV_SQLTYPE_INT), 
                                            array('15.99', null, null, SQLSRV_SQLTYPE_FLOAT), 
                                            array('2016-02-01', null, null, SQLSRV_SQLTYPE_DATETIME), 
                                            array(null, null, null, SQLSRV_SQLTYPE_VARCHAR(20)), 
                                            array('P003', null, null, SQLSRV_SQLTYPE_CHAR(4)), 
                                            array('USB connector', null, null, SQLSRV_SQLTYPE_VARCHAR(128)), 
                                            array('1652', null, null, SQLSRV_SQLTYPE_SMALLINT), 
                                            array('31', null, null, SQLSRV_SQLTYPE_INT), 
                                            array('9.99', null, null, SQLSRV_SQLTYPE_FLOAT), 
                                            array('2016-02-01', null, null, SQLSRV_SQLTYPE_DATETIME), 
                                            array(null, null, null, SQLSRV_SQLTYPE_VARCHAR(20))));
} else {
    $sql = "INSERT INTO $tableName1 VALUES
                    ('P001', 'Pencil 2B', '102', '24', '0.24', '2016-02-01', 'Red'), 
                    ('P002', 'Notepad', '102', '12', '3.87',  '2016-02-21', Null), 
                    ('P001', 'Mirror 2\"', '652', '3', '15.99',  '2016-02-01', NULL), 
                    ('P003', 'USB connector', '1652', '31', '9.99', '2016-02-01', NULL)";
    $stmt = sqlsrv_query($conn, $sql);
}
if (!$stmt) {
    fatalError("Failed to insert test data into $tableName1\n");
}

// Create table Country
$columns = array(new AE\ColumnMeta('CHAR(4)', 'SerialNumber'),
                 new AE\ColumnMeta('VARCHAR(2)', 'Code'));
AE\createTable($conn, $tableName2, $columns);

// Insert data
if (AE\isColEncrypted()) {
    $sql = "INSERT INTO $tableName2 VALUES (?, ?), (?, ?), (?, ?)";
    $stmt = sqlsrv_query($conn, $sql, array(array('P001', null, null, SQLSRV_SQLTYPE_CHAR(4)), 
                                            array('FR', null, null, SQLSRV_SQLTYPE_VARCHAR(2)),
                                            array('P002', null, null, SQLSRV_SQLTYPE_CHAR(4)), 
                                            array('UK', null, null, SQLSRV_SQLTYPE_VARCHAR(2)),
                                            array('P003', null, null, SQLSRV_SQLTYPE_CHAR(4)), 
                                            array('DE', null, null, SQLSRV_SQLTYPE_VARCHAR(2))));
} else {
    $sql = "INSERT INTO $tableName2 VALUES ('P001', 'FR'), ('P002', 'UK'), ('P003', 'DE')";
    $stmt = sqlsrv_query($conn, $sql);
}
if (!$stmt) {
    fatalError("Failed to insert test data into $tableName2\n");
}

// With AE enabled, we cannot do comparisons with encrypted columns
// Also, only forward cursor or client buffer is supported
if (AE\isColEncrypted()) {
    $sql = "SELECT личное_имя, SafetyStockLevel, StockedQty, UnitPrice, Color, Code
             FROM $tableName1 AS Purchasing
             JOIN $tableName2 AS Country
             ON Purchasing.ID = Country.SerialNumber
             WHERE Purchasing.личное_имя != ?
             AND Purchasing.StockedQty != ?
             AND Purchasing.DueDate= ?";
             
    $params = array('Notepad', 3, '2016-02-01');
    $stmt = sqlsrv_prepare($conn, $sql, $params, array("Scrollable"=>"buffered"));
    if ($stmt) {
        $res = sqlsrv_execute($stmt);
        if (!$res) {
            fatalError("Error in statement execution.\n");
        }
    } else {
        fatalError("Error in preparing statement.\n");
    }
} else {
    $sql = "SELECT личное_имя, SafetyStockLevel, StockedQty, UnitPrice, Color, Code
             FROM $tableName1 AS Purchasing
             JOIN $tableName2 AS Country
             ON Purchasing.ID = Country.SerialNumber
             WHERE Purchasing.StockedQty < ?
             AND Purchasing.UnitPrice < ?
             AND Purchasing.DueDate= ?";
             
    $params = array(100, '10.5', '2016-02-01');
    $stmt = sqlsrv_query($conn, $sql, $params, array("Scrollable"=>"static"));
    if (!$stmt) {
        fatalError("Error in statement execution.\n");
    }
}

// Iterate through the result set
// $product is an instance of the Product class
$i=0;
$hasNext = true;

while ($hasNext) {
    $sample = sqlsrv_fetch_object($stmt, "Sample", array($i+1000), SQLSRV_SCROLL_ABSOLUTE, $i);

    if (!$sample) {
        $hasNext = false;
    } else {
        $sample->report_output();
        $i++;
    }
}

dropTable($conn, $tableName1);
dropTable($conn, $tableName2);

// Free statement and connection resources
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
