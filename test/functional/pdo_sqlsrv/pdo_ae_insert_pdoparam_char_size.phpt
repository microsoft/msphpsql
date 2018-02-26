--TEST--
Test for inserting and retrieving encrypted data of string types
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
    $conn = connect();
    foreach ($dataTypes as $dataType) {
        $maxtype = strpos($dataType, "(max)");
        foreach ($lengths as $length) {
            if ($maxtype !== false) {
                $type = $dataType;
            } else {
                $type = "$dataType($length)";
            }
            echo "\nTesting $type:\n";
                
            foreach($encTypes as $encType) {
                //create and populate table
                $tbname = getTableName();
                $colMetaArr = array(new ColumnMeta($type, "c1", null, $encType));
                createTable($conn, $tbname, $colMetaArr);
                $input = str_repeat("d", $length);
                
                // prepare statement for inserting into table
                foreach ($pdoParamTypes as $pdoParamType) {
                    // insert a row
                    $r;
                    $stmt = insertRow($conn, $tbname, array( "c1" => new BindParamOp(1, $input, $pdoParamType)), "prepareBindParam", $r);
                    
                    if ($r === false) {
                        isIncompatibleTypesError($stmt, $type, $pdoParamType);
                    } else {
                        $sql = "SELECT c1 FROM $tbname";
                        $stmt = $conn->query($sql);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (strlen($row['c1']) == $length) {
                            echo "****PDO param type $pdoParamType is compatible with $encType encrypted $type****\n";
                        } else {
                            echo "PDO param type $pdoParamType is incompatible with $encType encrypted $type\n";
                        }
                    }
                    $conn->query("TRUNCATE TABLE $tbname");
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
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted char(1)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted char(1)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted char(1)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(1)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(1)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted char(1)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted char(1)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted char(1)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(1)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(1)****

Testing char(8):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted char(8)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted char(8)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted char(8)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(8)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(8)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted char(8)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted char(8)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted char(8)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(8)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(8)****

Testing char(64):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted char(64)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted char(64)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted char(64)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(64)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(64)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted char(64)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted char(64)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted char(64)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(64)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(64)****

Testing char(512):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted char(512)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted char(512)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted char(512)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(512)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(512)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted char(512)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted char(512)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted char(512)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(512)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(512)****

Testing char(4096):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted char(4096)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted char(4096)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted char(4096)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(4096)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(4096)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted char(4096)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted char(4096)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted char(4096)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(4096)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(4096)****

Testing char(8000):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted char(8000)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted char(8000)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted char(8000)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted char(8000)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted char(8000)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted char(8000)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted char(8000)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted char(8000)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted char(8000)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted char(8000)****

Testing varchar(1):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(1)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(1)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(1)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(1)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(1)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(1)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(1)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(1)****

Testing varchar(8):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(8)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(8)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(8)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(8)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(8)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(8)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(8)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(8)****

Testing varchar(64):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(64)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(64)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(64)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(64)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(64)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(64)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(64)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(64)****

Testing varchar(512):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(512)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(512)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(512)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(512)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(512)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(512)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(512)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(512)****

Testing varchar(4096):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(4096)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(4096)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(4096)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(4096)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(4096)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(4096)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(4096)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(4096)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(4096)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(4096)****

Testing varchar(8000):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(8000)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(8000)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(8000)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(8000)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(8000)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(8000)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(8000)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(8000)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(8000)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(8000)****

Testing varchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****

Testing varchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted varchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted varchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted varchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted varchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted varchar(max)****