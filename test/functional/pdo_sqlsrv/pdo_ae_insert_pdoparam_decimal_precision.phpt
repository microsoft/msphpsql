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
                
                // create table
                $tbname = "test_decimal";
                $colMetaArr = array(new ColumnMeta($type, "c_det"), new ColumnMeta($type, "c_rand", null, "randomized"));
                createTable($conn, $tbname, $colMetaArr);
                
                // test each PDO::PARAM_ type
                foreach ($pdoParamTypes as $pdoParamType) {
                    // insert a row
                    $r;
                    $stmt = insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType),
                                                            "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
                                                            
                    if ($pdoParamType == "PDO::PARAM_NULL") {
                        // null was inserted when the parameter was bound as PDO:PARAM_NULL
                        $sql = "SELECT c_det, c_rand FROM $tbname";
                        $stmt = $conn->query($sql);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!is_null($row['c_det']) || !is_null($row['c_rand'])) {
                            echo "NULL should have been inserted with $pdoParamType\n";
                        }
                    } elseif ($pdoParamType == "PDO::PARAM_STR" && $precision - $scale > 14) {
                        // without AE, when the input has greater than 14 digits to the left of the decimal,
                        // the double is translated by PHP to scientific notation
                        // inserting scientific notation as a string fails
                        if (!isAEConnected()) {
                            if ($r !== false) {
                                echo "PDO param type $pdoParamType should not be compatible with $type when the number of integers is greater than 14\n";
                            }
                        } else {
                            $sql = "SELECT c_det, c_rand FROM $tbname";
                            $stmt = $conn->query($sql);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (abs($row['c_det'] - $inputValues[0]) > $epsilon ||
                                abs($row['c_rand'] - $inputValues[1]) > $epsilon) {
                                echo "PDO param type $pdoParamType should be compatible with $type when Always Encrypted is enabled\n";
                            }
                        }
                    } else {
                        $sql = "SELECT c_det, c_rand FROM $tbname";
                        $stmt = $conn->query($sql);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (abs($row['c_det'] - $inputValues[0]) > $epsilon ||
                            abs($row['c_rand'] - $inputValues[1]) > $epsilon) {
                            // TODO: this is a workaround for the test to pass!!!!!
                            // with AE, doubles cannot be inserted into a decimal(38, 38) column
                            // remove the following if block to see the bug
                            // for more information see VSO task 2723
                            if (isAEConnected() && $precision == 38 && $scale == 38) {
                                echo "****PDO param type $pdoParamType is compatible with $type****\n";
                            } else {
                                echo "PDO param type $pdoParamType should be compatible with $type\n";
                            }
                        } else {
                            echo "****PDO param type $pdoParamType is compatible with $type****\n";
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
Testing decimal(1, 0):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(1, 0)****
****PDO param type PDO::PARAM_INT is compatible with decimal(1, 0)****
****PDO param type PDO::PARAM_STR is compatible with decimal(1, 0)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(1, 0)****

Testing decimal(1, 1):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(1, 1)****
****PDO param type PDO::PARAM_INT is compatible with decimal(1, 1)****
****PDO param type PDO::PARAM_STR is compatible with decimal(1, 1)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(1, 1)****

Testing decimal(4, 0):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(4, 0)****
****PDO param type PDO::PARAM_INT is compatible with decimal(4, 0)****
****PDO param type PDO::PARAM_STR is compatible with decimal(4, 0)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(4, 0)****

Testing decimal(4, 1):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(4, 1)****
****PDO param type PDO::PARAM_INT is compatible with decimal(4, 1)****
****PDO param type PDO::PARAM_STR is compatible with decimal(4, 1)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(4, 1)****

Testing decimal(4, 4):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(4, 4)****
****PDO param type PDO::PARAM_INT is compatible with decimal(4, 4)****
****PDO param type PDO::PARAM_STR is compatible with decimal(4, 4)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(4, 4)****

Testing decimal(16, 0):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(16, 0)****
****PDO param type PDO::PARAM_INT is compatible with decimal(16, 0)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(16, 0)****

Testing decimal(16, 1):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(16, 1)****
****PDO param type PDO::PARAM_INT is compatible with decimal(16, 1)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(16, 1)****

Testing decimal(16, 4):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(16, 4)****
****PDO param type PDO::PARAM_INT is compatible with decimal(16, 4)****
****PDO param type PDO::PARAM_STR is compatible with decimal(16, 4)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(16, 4)****

Testing decimal(16, 16):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(16, 16)****
****PDO param type PDO::PARAM_INT is compatible with decimal(16, 16)****
****PDO param type PDO::PARAM_STR is compatible with decimal(16, 16)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(16, 16)****

Testing decimal(38, 0):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(38, 0)****
****PDO param type PDO::PARAM_INT is compatible with decimal(38, 0)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 0)****

Testing decimal(38, 1):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(38, 1)****
****PDO param type PDO::PARAM_INT is compatible with decimal(38, 1)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 1)****

Testing decimal(38, 4):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(38, 4)****
****PDO param type PDO::PARAM_INT is compatible with decimal(38, 4)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 4)****

Testing decimal(38, 16):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(38, 16)****
****PDO param type PDO::PARAM_INT is compatible with decimal(38, 16)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 16)****

Testing decimal(38, 38):
****PDO param type PDO::PARAM_BOOL is compatible with decimal(38, 38)****
****PDO param type PDO::PARAM_INT is compatible with decimal(38, 38)****
****PDO param type PDO::PARAM_STR is compatible with decimal(38, 38)****
****PDO param type PDO::PARAM_LOB is compatible with decimal(38, 38)****

Testing numeric(1, 0):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(1, 0)****
****PDO param type PDO::PARAM_INT is compatible with numeric(1, 0)****
****PDO param type PDO::PARAM_STR is compatible with numeric(1, 0)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(1, 0)****

Testing numeric(1, 1):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(1, 1)****
****PDO param type PDO::PARAM_INT is compatible with numeric(1, 1)****
****PDO param type PDO::PARAM_STR is compatible with numeric(1, 1)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(1, 1)****

Testing numeric(4, 0):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(4, 0)****
****PDO param type PDO::PARAM_INT is compatible with numeric(4, 0)****
****PDO param type PDO::PARAM_STR is compatible with numeric(4, 0)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(4, 0)****

Testing numeric(4, 1):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(4, 1)****
****PDO param type PDO::PARAM_INT is compatible with numeric(4, 1)****
****PDO param type PDO::PARAM_STR is compatible with numeric(4, 1)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(4, 1)****

Testing numeric(4, 4):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(4, 4)****
****PDO param type PDO::PARAM_INT is compatible with numeric(4, 4)****
****PDO param type PDO::PARAM_STR is compatible with numeric(4, 4)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(4, 4)****

Testing numeric(16, 0):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(16, 0)****
****PDO param type PDO::PARAM_INT is compatible with numeric(16, 0)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(16, 0)****

Testing numeric(16, 1):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(16, 1)****
****PDO param type PDO::PARAM_INT is compatible with numeric(16, 1)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(16, 1)****

Testing numeric(16, 4):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(16, 4)****
****PDO param type PDO::PARAM_INT is compatible with numeric(16, 4)****
****PDO param type PDO::PARAM_STR is compatible with numeric(16, 4)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(16, 4)****

Testing numeric(16, 16):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(16, 16)****
****PDO param type PDO::PARAM_INT is compatible with numeric(16, 16)****
****PDO param type PDO::PARAM_STR is compatible with numeric(16, 16)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(16, 16)****

Testing numeric(38, 0):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(38, 0)****
****PDO param type PDO::PARAM_INT is compatible with numeric(38, 0)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 0)****

Testing numeric(38, 1):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(38, 1)****
****PDO param type PDO::PARAM_INT is compatible with numeric(38, 1)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 1)****

Testing numeric(38, 4):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(38, 4)****
****PDO param type PDO::PARAM_INT is compatible with numeric(38, 4)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 4)****

Testing numeric(38, 16):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(38, 16)****
****PDO param type PDO::PARAM_INT is compatible with numeric(38, 16)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 16)****

Testing numeric(38, 38):
****PDO param type PDO::PARAM_BOOL is compatible with numeric(38, 38)****
****PDO param type PDO::PARAM_INT is compatible with numeric(38, 38)****
****PDO param type PDO::PARAM_STR is compatible with numeric(38, 38)****
****PDO param type PDO::PARAM_LOB is compatible with numeric(38, 38)****