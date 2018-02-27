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

$dataTypes = array("char", "varchar", "varchar(max)");
$lengths = array(1, 8, 64, 512, 4096, 8000);
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
Testing char(1):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(1)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(1)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(1)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(1)****

Testing char(8):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(8)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(8)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(8)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(8)****

Testing char(64):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(64)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(64)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(64)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(64)****

Testing char(512):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(512)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(512)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(512)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(512)****

Testing char(4096):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(4096)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(4096)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(4096)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(4096)****

Testing char(8000):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(8000)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(8000)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(8000)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(8000)****

Testing varchar(1):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(1)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(1)****

Testing varchar(8):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(8)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(8)****

Testing varchar(64):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(64)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(64)****

Testing varchar(512):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(512)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(512)****

Testing varchar(4096):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(4096)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(4096)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(4096)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(4096)****

Testing varchar(8000):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(8000)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(8000)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(8000)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(8000)****

Testing varchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****