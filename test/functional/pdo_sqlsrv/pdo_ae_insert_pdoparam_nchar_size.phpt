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

$dataTypes = array("nchar", "nvarchar", "nvarchar(max)");
$lengths = array(1, 8, 64, 512, 4000);
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
Testing nchar(1):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nchar(1)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nchar(1)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nchar(1)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(1)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nchar(1)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nchar(1)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nchar(1)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(1)****

Testing nchar(8):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nchar(8)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nchar(8)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nchar(8)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(8)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nchar(8)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nchar(8)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nchar(8)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(8)****

Testing nchar(64):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nchar(64)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nchar(64)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nchar(64)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(64)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nchar(64)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nchar(64)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nchar(64)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(64)****

Testing nchar(512):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nchar(512)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nchar(512)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nchar(512)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(512)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nchar(512)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nchar(512)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nchar(512)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(512)****

Testing nchar(4000):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nchar(4000)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nchar(4000)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nchar(4000)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nchar(4000)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nchar(4000)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nchar(4000)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nchar(4000)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nchar(4000)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nchar(4000)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nchar(4000)****

Testing nvarchar(1):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(1)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(1)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(1)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(1)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(1)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(1)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(1)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(1)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(1)****

Testing nvarchar(8):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(8)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(8)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(8)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(8)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(8)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(8)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(8)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(8)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(8)****

Testing nvarchar(64):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(64)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(64)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(64)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(64)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(64)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(64)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(64)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(64)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(64)****

Testing nvarchar(512):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(512)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(512)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(512)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(512)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(512)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(512)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(512)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(512)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(512)****

Testing nvarchar(4000):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(4000)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(4000)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(4000)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(4000)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(4000)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(4000)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(4000)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(4000)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(4000)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(4000)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****

Testing nvarchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with deterministic encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with deterministic encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with deterministic encrypted nvarchar(max)****
****PDO param type PDO::PARAM_BOOL is compatible with randomized encrypted nvarchar(max)****
PDO param type PDO::PARAM_NULL is incompatible with randomized encrypted nvarchar(max)
****PDO param type PDO::PARAM_INT is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_STR is compatible with randomized encrypted nvarchar(max)****
****PDO param type PDO::PARAM_LOB is compatible with randomized encrypted nvarchar(max)****