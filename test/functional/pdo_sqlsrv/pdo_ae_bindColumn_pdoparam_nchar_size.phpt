--TEST--
Test for inserting and retrieving encrypted data of numeric types
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array("nchar", "nvarchar", "nvarchar(max)");
$lengths = array(1, 8, 64, 512, 4000);
$encTypes = array("deterministic", "randomized");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        $maxtype = strpos($dataType, "(max)");
        foreach ($lengths as $length) {
            if ($maxtype !== false) {
                $type = $dataType;
            } else {
                $type = "$dataType($length)";
            }
            echo "\nTesting $type:\n";
                
            //create and populate table
            foreach($encTypes as $encType) {
                $tbname = getTableName();
                $colMetaArr = array(new ColumnMeta($type, "c1", null, $encType));
                createTable($conn, $tbname, $colMetaArr);
                $input = str_repeat("d", $length);
                insertRow($conn, $tbname, array("c1" => $input));
                    
                // fetch with PDO::bindColumn and PDO::FETCH_BOUND
                $query = "SELECT c1 FROM $tbname";
                foreach ($pdoParamTypes as $pdoParamType) {
                    $det = "";
                    $rand = "";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $stmt->bindColumn('c1', $c1, constant($pdoParamType));
                    $row = $stmt->fetch(PDO::FETCH_BOUND);
                   if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_NULL" || $pdoParamType == "PDO::PARAM_INT") {
                        if (!empty($det) || !empty($rand)) {
                            echo "Fetching $type as PDO param type $pdoParamType should be empty\n";
                        }
                    } else {
                        if (strlen($c1) == $length) {
                            echo "****PDO param type $pdoParamType is compatible with $encType encrypted $type****\n";
                        } else {
                             echo "PDO param type $pdoParamType is incompatible with $encType encrypted $type\n";
                        }
                    }
                }
                dropTable($conn, $tbname);
            }
        }
    }
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
Testing nchar(1):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(1)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(1)****

Testing nchar(8):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(8)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(8)****

Testing nchar(64):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(64)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(64)****

Testing nchar(512):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(512)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(512)****

Testing nchar(4000):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(4000)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(4000)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(4000)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(4000)****

Testing nvarchar(1):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(1)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(1)****

Testing nvarchar(8):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(8)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(8)****

Testing nvarchar(64):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(64)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(64)****

Testing nvarchar(512):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(512)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(512)****

Testing nvarchar(4000):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(4000)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(4000)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(4000)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(4000)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****