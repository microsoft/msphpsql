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

$dataTypes = array("decimal", "numeric");
$precisions = array(1 => array(0, 1), 
                    4 => array(0, 1, 4), 
                    16 => array(0, 1, 4, 16), 
                    38 => array(0, 1, 4, 16, 38));
$inputValuesInit = array(92233720368547758089223372036854775808, -92233720368547758089223372036854775808);
$inputPrecision = 38;

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        foreach ($precisions as $precision => $scales) {
            foreach ($scales as $scale) {
                // change the input values depending on the precision and scale
                $precDiff = $inputPrecision - ($precision - $scale);
                $inputValues = $inputValuesInit;
                foreach ($inputValues as &$inputValue) {
                    $inputValue = $inputValue / pow(10, $precDiff);
                }
                // epsilon for comparing doubles
                // float in PHP only has a precision of roughtly 14 digits: http://php.net/manual/en/language.types.float.php
                $epsilon;
                if ($precision < 14) {
                    $epsilon = pow(10, $scale * -1);
                } else {
                    $numint = $precision - $scale;
                    if ($numint < 14) {
                        $epsilon = pow(10, (14 - $numint) * -1);
                    } else {
                        $epsilon = pow(10, $numint - 14);
                    }
                }
                
                $type = "$dataType($precision, $scale)";
                echo "\nTesting $type:\n";
                
                //create and populate table
                $tbname = "test_decimal";
                $colMetaArr = array(new ColumnMeta($type, "c_det"), new ColumnMeta($type, "c_rand", null, "randomized"));
                createTable($conn, $tbname, $colMetaArr);
                insertRow($conn, $tbname, array("c_det" => $inputValues[0], "c_rand" => $inputValues[1]));
                
                // fetch with PDO::bindColumn and PDO::FETCH_BOUND
                $query = "SELECT c_det, c_rand FROM $tbname";
                foreach ($pdoParamTypes as $pdoParamType) {
                    $det = "";
                    $rand = "";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $stmt->bindColumn('c_det', $det, constant($pdoParamType));
                    $stmt->bindColumn('c_rand', $rand, constant($pdoParamType));
                    $row = $stmt->fetch(PDO::FETCH_BOUND);
                    
                    // assumes the correct behavior of fetching decimal types as PDO::PARAM_BOOL, PDO::PARAM_NULL and PDO::PARAM_INT is to return NULL
                    // behavior for fetching decimals as PARAM_BOOL and PARAM_INT varies depending on the number being fetched:
                    // 1. if the number is less than 1, returns 0 (even though the number being fetched is 0.9)
                    // 2. if the number is greater than 1 and the number of digits is less than 11, returns the correctly rounded integer (e.g., returns 922 when fetching 922.3)
                    // 3. if the number is greater than 1 and the number of digits is greater than 11, returns NULL
                    // see VSO item 2730
                    if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_NULL" || $pdoParamType == "PDO::PARAM_INT") {
                        if (!is_null($det) || !is_null($rand)) {
                            echo "Fetching $type as PDO param type $pdoParamType should return NULL\n";
                        }
                    } else {
                        if (abs($det - $inputValues[0]) > $epsilon ||
                            abs($rand - $inputValues[1]) > $epsilon) {
                            echo "PDO param type $pdoParamType should be compatible with $type\n";
                        } else {
                            echo "****PDO param type $pdoParamType is compatible with $type****\n";
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
Testing decimal(1, 0):
Fetching decimal(1, 0) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching decimal(1, 0) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with decimal(1, 0)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(1, 0)****

Testing decimal(1, 1):
Fetching decimal(1, 1) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching decimal(1, 1) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with decimal(1, 1)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(1, 1)****

Testing decimal(4, 0):
Fetching decimal(4, 0) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching decimal(4, 0) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with decimal(4, 0)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(4, 0)****

Testing decimal(4, 1):
Fetching decimal(4, 1) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching decimal(4, 1) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with decimal(4, 1)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(4, 1)****

Testing decimal(4, 4):
Fetching decimal(4, 4) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching decimal(4, 4) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with decimal(4, 4)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(4, 4)****

Testing decimal(16, 0):
****PDO param type PDO::PARAM_STR is compatible with decimal(16, 0)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(16, 0)****

Testing decimal(16, 1):
****PDO param type PDO::PARAM_STR is compatible with decimal(16, 1)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(16, 1)****

Testing decimal(16, 4):
****PDO param type PDO::PARAM_STR is compatible with decimal(16, 4)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(16, 4)****

Testing decimal(16, 16):
Fetching decimal(16, 16) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching decimal(16, 16) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with decimal(16, 16)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(16, 16)****

Testing decimal(38, 0):
****PDO param type PDO::PARAM_STR is compatible with decimal(38, 0)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 0)****

Testing decimal(38, 1):
****PDO param type PDO::PARAM_STR is compatible with decimal(38, 1)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 1)****

Testing decimal(38, 4):
****PDO param type PDO::PARAM_STR is compatible with decimal(38, 4)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 4)****

Testing decimal(38, 16):
****PDO param type PDO::PARAM_STR is compatible with decimal(38, 16)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 16)****

Testing decimal(38, 38):
Fetching decimal(38, 38) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching decimal(38, 38) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with decimal(38, 38)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 38)****

Testing numeric(1, 0):
Fetching numeric(1, 0) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching numeric(1, 0) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with numeric(1, 0)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(1, 0)****

Testing numeric(1, 1):
Fetching numeric(1, 1) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching numeric(1, 1) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with numeric(1, 1)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(1, 1)****

Testing numeric(4, 0):
Fetching numeric(4, 0) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching numeric(4, 0) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with numeric(4, 0)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(4, 0)****

Testing numeric(4, 1):
Fetching numeric(4, 1) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching numeric(4, 1) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with numeric(4, 1)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(4, 1)****

Testing numeric(4, 4):
Fetching numeric(4, 4) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching numeric(4, 4) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with numeric(4, 4)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(4, 4)****

Testing numeric(16, 0):
****PDO param type PDO::PARAM_STR is compatible with numeric(16, 0)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(16, 0)****

Testing numeric(16, 1):
****PDO param type PDO::PARAM_STR is compatible with numeric(16, 1)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(16, 1)****

Testing numeric(16, 4):
****PDO param type PDO::PARAM_STR is compatible with numeric(16, 4)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(16, 4)****

Testing numeric(16, 16):
Fetching numeric(16, 16) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching numeric(16, 16) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with numeric(16, 16)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(16, 16)****

Testing numeric(38, 0):
****PDO param type PDO::PARAM_STR is compatible with numeric(38, 0)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 0)****

Testing numeric(38, 1):
****PDO param type PDO::PARAM_STR is compatible with numeric(38, 1)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 1)****

Testing numeric(38, 4):
****PDO param type PDO::PARAM_STR is compatible with numeric(38, 4)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 4)****

Testing numeric(38, 16):
****PDO param type PDO::PARAM_STR is compatible with numeric(38, 16)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 16)****

Testing numeric(38, 38):
Fetching numeric(38, 38) as PDO param type PDO::PARAM_BOOL should return NULL
Fetching numeric(38, 38) as PDO param type PDO::PARAM_INT should return NULL
****PDO param type PDO::PARAM_STR is compatible with numeric(38, 38)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 38)****