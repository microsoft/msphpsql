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

    public function report_output($dummy)
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

    public function report_output($unitPrice)
    {
        echo "ID: ".$this->objID."\n";
        echo "Name: ".$this->личное_имя."\n";
        
        // Since UnitPrice column is of type FLOAT,
        // should not expect the values to match exactly
        $epsilon = 0.00001;
        $diff = abs(($this->UnitPrice - $unitPrice) / $unitPrice);
        
        if ($diff > $epsilon) {
            echo "Expected $unitPrice [EUR] but got ";
            echo "Unit Price: ".$this->getPrice()."\n";
        }
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

function insertInputsSimple($conn, $tableName, $data)
{
    $sql = "INSERT INTO $tableName VALUES \n";
    for ($i = 0; $i < count($data); $i++) {
        $sql .= '(';
        for ($j = 0; $j < count($data[$i]); $j++) {
            if (is_null($data[$i][$j])) {
                $sql .= 'NULL';
            } else {
                $sql .= "'" . $data[$i][$j] . "'";
            }
            
            if ($j < count($data[$i]) - 1) {
                $sql .= ",";
            } 
        }
        if ($i < count($data) - 1) {
            $sql .= "),\n";
        } else {
            $sql .= ") \n";
        }
    }
    
    return sqlsrv_query($conn, $sql);
}

function insertInputsWithSQLTypes($conn, $tableName, $data, $sqlTypes)
{
    $sql = "INSERT INTO $tableName VALUES
                    (?, ?, ?, ?, ?, ?, ?), 
                    (?, ?, ?, ?, ?, ?, ?), 
                    (?, ?, ?, ?, ?, ?, ?), 
                    (?, ?, ?, ?, ?, ?, ?)";
    
    $params = array();
    for ($i = 0; $i < count($data); $i++) {
        for ($j = 0; $j < count($data[$i]); $j++) {
            $params2 = array($data[$i][$j], null, null, $sqlTypes[$j]);
            array_push($params, $params2);
        }
    }
    return sqlsrv_query($conn, $sql, $params);
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

// Input data for $tableName1
$data = array(array('P001', 'Pencil 2B', '102', '24', '0.24', '2016-02-01', 'Red'), 
              array('P002', 'Notepad', '102', '12', '3.87',  '2016-02-21', Null), 
              array('P001', 'Mirror 2\"', '652', '3', '15.99',  '2016-02-01', NULL), 
              array('P003', 'USB connector', '1652', '31', '9.99', '2016-02-01', NULL));

$sqlTypes = array(SQLSRV_SQLTYPE_CHAR(4), 
                  SQLSRV_SQLTYPE_VARCHAR(128),
                  SQLSRV_SQLTYPE_SMALLINT,
                  SQLSRV_SQLTYPE_INT,
                  SQLSRV_SQLTYPE_FLOAT,
                  SQLSRV_SQLTYPE_DATETIME,
                  SQLSRV_SQLTYPE_VARCHAR(20));

if (AE\isColEncrypted()) {
    $stmt = insertInputsWithSQLTypes($conn, $tableName1, $data, $sqlTypes);
} else {
    $stmt = insertInputsSimple($conn, $tableName1, $data);
}
if (!$stmt) {
    fatalError("Failed to insert test data into $tableName1\n");
}

// Create table Country
$columns = array(new AE\ColumnMeta('CHAR(4)', 'SerialNumber'),
                 new AE\ColumnMeta('VARCHAR(2)', 'Code'));
AE\createTable($conn, $tableName2, $columns);

// Insert data for for $tableName2
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

// With AE enabled (without secure enclave), do not do comparisons with encrypted columns
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

// Iterate through the result set - expect only the first and last sets of input $data
// $product is an instance of the Product class
$expected = array(getInputData1($data[0]), getInputData1($data[count($data)-1]));

$i = 0;
$hasNext = true;
while ($hasNext) {
    $sample = sqlsrv_fetch_object($stmt, "Sample", array($i+1000), SQLSRV_SCROLL_ABSOLUTE, $i);

    if (!$sample) {
        $hasNext = false;
    } else {
        $sample->report_output($expected[$i]['UnitPrice']);
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
ID: 1001
Name: USB connector
Done
