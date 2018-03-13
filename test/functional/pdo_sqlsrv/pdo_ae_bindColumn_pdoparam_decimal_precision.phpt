--TEST--
Test for retrieving encrypted data from decimal types columns using PDO::bindColumn
--DESCRIPTION--
Test conversion from decimal types column to output of PDO::PARAM types
With or without ALways Encrypted, conversion works if:
1. From any decimal type column to PDO::PARAM_STR
2. From any decimal type column to PDO::PARAM_LOB
TODO: behavior for teching decimals as PARAM_BOOL and PARAM_INT varies depending on the number being fetched
      1. if the number is less than 1, returns 0 (even though the number being fetched is 0.9)
      2. if the number is greater than 1 and the number of digits is less than 11, returns the correctly rounded integer (e.g., returns 922 when fetching 922.3)
      3. if the number is greater than 1 and the number of digits is greater than 11, returns NULL
      need to investigate which should be the correct behavior
      for this test, assume to correct behavior is to return NULL
      documented in VSO 2730
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
        foreach ($precisions as $m1 => $scales) {
            foreach ($scales as $m2) {
                // change the number of integers in the input values to be $m1 - $m2
                $precDiff = $inputPrecision - ($m1 - $m2);
                $inputValues = $inputValuesInit;
                foreach ($inputValues as &$inputValue) {
                    $inputValue = $inputValue / pow(10, $precDiff);
                }
                
                // compute the epsilon for comparing doubles
                // float in PHP only has a precision of roughtly 14 digits: http://php.net/manual/en/language.types.float.php
                $epsilon;
                if ($m1 < 14) {
                    $epsilon = pow(10, $m2 * -1);
                } else {
                    $numint = $m1 - $m2;
                    if ($numint < 14) {
                        $epsilon = pow(10, (14 - $numint) * -1);
                    } else {
                        $epsilon = pow(10, $numint - 14);
                    }
                }
                
                $typeFull = "$dataType($m1, $m2)";
                echo "\nTesting $typeFull:\n";
                
                // create and populate table containing decimal(m1, m2) or numeric(m1, m2) columns
                $tbname = "test_" . $dataType . $m1 . $m2;
                $colMetaArr = array(new ColumnMeta($typeFull, "c_det"), new ColumnMeta($typeFull, "c_rand", null, "randomized"));
                createTable($conn, $tbname, $colMetaArr);
                insertRow($conn, $tbname, array("c_det" => $inputValues[0], "c_rand" => $inputValues[1]));
                
                // fetch by specifying PDO::PARAM_ types with PDO::bindColumn
                $query = "SELECT c_det, c_rand FROM $tbname";
                foreach ($pdoParamTypes as $pdoParamType) {
                    $det = "";
                    $rand = "";
                    $stmt = $conn->prepare($query);
                    $stmt->execute();
                    $stmt->bindColumn('c_det', $det, constant($pdoParamType));
                    $stmt->bindColumn('c_rand', $rand, constant($pdoParamType));
                    $row = $stmt->fetch(PDO::FETCH_BOUND);
                    
                    // check the case when fetching as PDO::PARAM_BOOL, PDO::PARAM_NULL or PDO::PARAM_INT
                    // with or without AE: should not work
                    // assume to correct behavior is to return NULL, see description
                    if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_NULL" || $pdoParamType == "PDO::PARAM_INT") {
                        if (!is_null($det) || !is_null($rand)) {
                            echo "Retrieving $typeFull data as $pdoParamType should return NULL\n";
                        }
                    } else {
                        if (abs($det - $inputValues[0]) < $epsilon &&
                            abs($rand - $inputValues[1]) < $epsilon) {
                            echo "****Retrieving $typeFull as $pdoParamType is supported****\n";
                        } else {
                            echo "Retrieving $typeFull as $pdoParamType fails\n";
                        }
                    }
                }
                // cleanup
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
Retrieving decimal(1, 0) data as PDO::PARAM_BOOL should return NULL
Retrieving decimal(1, 0) data as PDO::PARAM_INT should return NULL
****Retrieving decimal(1, 0) as PDO::PARAM_STR is supported****
****Retrieving decimal(1, 0) as PDO::PARAM_LOB is supported****

Testing decimal(1, 1):
Retrieving decimal(1, 1) data as PDO::PARAM_BOOL should return NULL
Retrieving decimal(1, 1) data as PDO::PARAM_INT should return NULL
****Retrieving decimal(1, 1) as PDO::PARAM_STR is supported****
****Retrieving decimal(1, 1) as PDO::PARAM_LOB is supported****

Testing decimal(4, 0):
Retrieving decimal(4, 0) data as PDO::PARAM_BOOL should return NULL
Retrieving decimal(4, 0) data as PDO::PARAM_INT should return NULL
****Retrieving decimal(4, 0) as PDO::PARAM_STR is supported****
****Retrieving decimal(4, 0) as PDO::PARAM_LOB is supported****

Testing decimal(4, 1):
Retrieving decimal(4, 1) data as PDO::PARAM_BOOL should return NULL
Retrieving decimal(4, 1) data as PDO::PARAM_INT should return NULL
****Retrieving decimal(4, 1) as PDO::PARAM_STR is supported****
****Retrieving decimal(4, 1) as PDO::PARAM_LOB is supported****

Testing decimal(4, 4):
Retrieving decimal(4, 4) data as PDO::PARAM_BOOL should return NULL
Retrieving decimal(4, 4) data as PDO::PARAM_INT should return NULL
****Retrieving decimal(4, 4) as PDO::PARAM_STR is supported****
****Retrieving decimal(4, 4) as PDO::PARAM_LOB is supported****

Testing decimal(16, 0):
****Retrieving decimal(16, 0) as PDO::PARAM_STR is supported****
****Retrieving decimal(16, 0) as PDO::PARAM_LOB is supported****

Testing decimal(16, 1):
****Retrieving decimal(16, 1) as PDO::PARAM_STR is supported****
****Retrieving decimal(16, 1) as PDO::PARAM_LOB is supported****

Testing decimal(16, 4):
****Retrieving decimal(16, 4) as PDO::PARAM_STR is supported****
****Retrieving decimal(16, 4) as PDO::PARAM_LOB is supported****

Testing decimal(16, 16):
Retrieving decimal(16, 16) data as PDO::PARAM_BOOL should return NULL
Retrieving decimal(16, 16) data as PDO::PARAM_INT should return NULL
****Retrieving decimal(16, 16) as PDO::PARAM_STR is supported****
****Retrieving decimal(16, 16) as PDO::PARAM_LOB is supported****

Testing decimal(38, 0):
****Retrieving decimal(38, 0) as PDO::PARAM_STR is supported****
****Retrieving decimal(38, 0) as PDO::PARAM_LOB is supported****

Testing decimal(38, 1):
****Retrieving decimal(38, 1) as PDO::PARAM_STR is supported****
****Retrieving decimal(38, 1) as PDO::PARAM_LOB is supported****

Testing decimal(38, 4):
****Retrieving decimal(38, 4) as PDO::PARAM_STR is supported****
****Retrieving decimal(38, 4) as PDO::PARAM_LOB is supported****

Testing decimal(38, 16):
****Retrieving decimal(38, 16) as PDO::PARAM_STR is supported****
****Retrieving decimal(38, 16) as PDO::PARAM_LOB is supported****

Testing decimal(38, 38):
Retrieving decimal(38, 38) data as PDO::PARAM_BOOL should return NULL
Retrieving decimal(38, 38) data as PDO::PARAM_INT should return NULL
****Retrieving decimal(38, 38) as PDO::PARAM_STR is supported****
****Retrieving decimal(38, 38) as PDO::PARAM_LOB is supported****

Testing numeric(1, 0):
Retrieving numeric(1, 0) data as PDO::PARAM_BOOL should return NULL
Retrieving numeric(1, 0) data as PDO::PARAM_INT should return NULL
****Retrieving numeric(1, 0) as PDO::PARAM_STR is supported****
****Retrieving numeric(1, 0) as PDO::PARAM_LOB is supported****

Testing numeric(1, 1):
Retrieving numeric(1, 1) data as PDO::PARAM_BOOL should return NULL
Retrieving numeric(1, 1) data as PDO::PARAM_INT should return NULL
****Retrieving numeric(1, 1) as PDO::PARAM_STR is supported****
****Retrieving numeric(1, 1) as PDO::PARAM_LOB is supported****

Testing numeric(4, 0):
Retrieving numeric(4, 0) data as PDO::PARAM_BOOL should return NULL
Retrieving numeric(4, 0) data as PDO::PARAM_INT should return NULL
****Retrieving numeric(4, 0) as PDO::PARAM_STR is supported****
****Retrieving numeric(4, 0) as PDO::PARAM_LOB is supported****

Testing numeric(4, 1):
Retrieving numeric(4, 1) data as PDO::PARAM_BOOL should return NULL
Retrieving numeric(4, 1) data as PDO::PARAM_INT should return NULL
****Retrieving numeric(4, 1) as PDO::PARAM_STR is supported****
****Retrieving numeric(4, 1) as PDO::PARAM_LOB is supported****

Testing numeric(4, 4):
Retrieving numeric(4, 4) data as PDO::PARAM_BOOL should return NULL
Retrieving numeric(4, 4) data as PDO::PARAM_INT should return NULL
****Retrieving numeric(4, 4) as PDO::PARAM_STR is supported****
****Retrieving numeric(4, 4) as PDO::PARAM_LOB is supported****

Testing numeric(16, 0):
****Retrieving numeric(16, 0) as PDO::PARAM_STR is supported****
****Retrieving numeric(16, 0) as PDO::PARAM_LOB is supported****

Testing numeric(16, 1):
****Retrieving numeric(16, 1) as PDO::PARAM_STR is supported****
****Retrieving numeric(16, 1) as PDO::PARAM_LOB is supported****

Testing numeric(16, 4):
****Retrieving numeric(16, 4) as PDO::PARAM_STR is supported****
****Retrieving numeric(16, 4) as PDO::PARAM_LOB is supported****

Testing numeric(16, 16):
Retrieving numeric(16, 16) data as PDO::PARAM_BOOL should return NULL
Retrieving numeric(16, 16) data as PDO::PARAM_INT should return NULL
****Retrieving numeric(16, 16) as PDO::PARAM_STR is supported****
****Retrieving numeric(16, 16) as PDO::PARAM_LOB is supported****

Testing numeric(38, 0):
****Retrieving numeric(38, 0) as PDO::PARAM_STR is supported****
****Retrieving numeric(38, 0) as PDO::PARAM_LOB is supported****

Testing numeric(38, 1):
****Retrieving numeric(38, 1) as PDO::PARAM_STR is supported****
****Retrieving numeric(38, 1) as PDO::PARAM_LOB is supported****

Testing numeric(38, 4):
****Retrieving numeric(38, 4) as PDO::PARAM_STR is supported****
****Retrieving numeric(38, 4) as PDO::PARAM_LOB is supported****

Testing numeric(38, 16):
****Retrieving numeric(38, 16) as PDO::PARAM_STR is supported****
****Retrieving numeric(38, 16) as PDO::PARAM_LOB is supported****

Testing numeric(38, 38):
Retrieving numeric(38, 38) data as PDO::PARAM_BOOL should return NULL
Retrieving numeric(38, 38) data as PDO::PARAM_INT should return NULL
****Retrieving numeric(38, 38) as PDO::PARAM_STR is supported****
****Retrieving numeric(38, 38) as PDO::PARAM_LOB is supported****