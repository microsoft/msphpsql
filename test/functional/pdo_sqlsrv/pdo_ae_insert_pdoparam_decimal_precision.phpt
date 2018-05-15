--TEST--
Test for inserting encrypted data into decimal types columns
--DESCRIPTION--
Test conversions between different decimal types
With Always Encrypted, implicit conversion works if:
1. From input of PDO::PARAM_BOOL to a any decimal column
2. From input of PDO::PARAM_INT to a any decimal column
3. From input of PDO::PARAM_STR to a any decimal column
4. From input of PDO::PARAM_LOB to a any decimal column
Without Always Encrypted, all of the above should work except for:
1. From input of PDO::PARAM_STR to a decimal column and the input has more than 14 digits to the left of the decimal
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
                
                // create table containing decimal(m1, m2) or numeric(m1, m2) columns
                $tbname = "test_" . $dataType . $m1 . $m2;
                $colMetaArr = array(new ColumnMeta($typeFull, "c_det"), new ColumnMeta($typeFull, "c_rand", null, "randomized"));
                createTable($conn, $tbname, $colMetaArr);
                
                // insert by specifying PDO::PARAM_ types
                foreach ($pdoParamTypes as $pdoParamType) {
                    $r;
                    $stmt = insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
                                     
                    // check the case when inserting as PDO::PARAM_NULL
                    // with or without AE: NULL is inserted
                    if ($pdoParamType == "PDO::PARAM_NULL") {
                        if ($r === false) {
                            echo "Conversion from $pdoParamType to $typeFull should be supported\n";
                        } else {
                            $sql = "SELECT c_det, c_rand FROM $tbname";
                            $stmt = $conn->query($sql);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (!is_null($row['c_det']) || !is_null($row['c_rand'])) {
                                echo "NULL should have been inserted with $pdoParamType\n";
                            }
                        }
                    // check the case when inserting as PDO::PARAM_STR and the input has more than 14 digits to the left of the decimal
                    // with AE: should work
                    // without AE: should not work
                    //             when the input has greater than 14 digits to the left of the decimal, the double is translated by PHP to scientific notation
                    //             inserting a scientific notation string fails
                    } elseif ($pdoParamType == "PDO::PARAM_STR" && $m1 - $m2 > 14) {
                        if (!isAEConnected()) {
                            if ($r !== false) {
                                echo "PDO param type $pdoParamType should not be compatible with $typeFull when the number of integers is greater than 14\n";
                            }
                        } else {
                            if ($r === false) {
                                echo "Conversion from $pdoParamType to $typeFull should be supported\n";
                            }
                            $sql = "SELECT c_det, c_rand FROM $tbname";
                            $stmt = $conn->query($sql);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (abs($row['c_det'] - $inputValues[0]) > $epsilon ||
                                abs($row['c_rand'] - $inputValues[1]) > $epsilon) {
                                echo "Conversion from $pdoParamType to $typeFull causes data corruption\n";
                            }
                        }
                    // check the case when inserting as PDO::PARAM_STR with input less than 14 digits to the left of the decimal
                    // and PDO::PARAM_BOOL, PDO::PARAM_INT or PDO::PARAM_LOB
                    // with or without AE: should work
                    } else {
                        if ($r === false) {
                            echo "Conversion from $pdoParamType to $typeFull should be supported\n";
                        }
                        $sql = "SELECT c_det, c_rand FROM $tbname";
                        $stmt = $conn->query($sql);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (abs($row['c_det'] - $inputValues[0]) > $epsilon ||
                            abs($row['c_rand'] - $inputValues[1]) > $epsilon) {
                            echo "Conversion from $pdoParamType to $typeFull causes data corruption\n";
                        } else {
                            echo "****Conversion from $pdoParamType to $typeFull is supported****\n";
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
****Conversion from PDO::PARAM_BOOL to decimal(1, 0) is supported****
****Conversion from PDO::PARAM_INT to decimal(1, 0) is supported****
****Conversion from PDO::PARAM_STR to decimal(1, 0) is supported****
****Conversion from PDO::PARAM_LOB to decimal(1, 0) is supported****

Testing decimal(1, 1):
****Conversion from PDO::PARAM_BOOL to decimal(1, 1) is supported****
****Conversion from PDO::PARAM_INT to decimal(1, 1) is supported****
****Conversion from PDO::PARAM_STR to decimal(1, 1) is supported****
****Conversion from PDO::PARAM_LOB to decimal(1, 1) is supported****

Testing decimal(4, 0):
****Conversion from PDO::PARAM_BOOL to decimal(4, 0) is supported****
****Conversion from PDO::PARAM_INT to decimal(4, 0) is supported****
****Conversion from PDO::PARAM_STR to decimal(4, 0) is supported****
****Conversion from PDO::PARAM_LOB to decimal(4, 0) is supported****

Testing decimal(4, 1):
****Conversion from PDO::PARAM_BOOL to decimal(4, 1) is supported****
****Conversion from PDO::PARAM_INT to decimal(4, 1) is supported****
****Conversion from PDO::PARAM_STR to decimal(4, 1) is supported****
****Conversion from PDO::PARAM_LOB to decimal(4, 1) is supported****

Testing decimal(4, 4):
****Conversion from PDO::PARAM_BOOL to decimal(4, 4) is supported****
****Conversion from PDO::PARAM_INT to decimal(4, 4) is supported****
****Conversion from PDO::PARAM_STR to decimal(4, 4) is supported****
****Conversion from PDO::PARAM_LOB to decimal(4, 4) is supported****

Testing decimal(16, 0):
****Conversion from PDO::PARAM_BOOL to decimal(16, 0) is supported****
****Conversion from PDO::PARAM_INT to decimal(16, 0) is supported****
****Conversion from PDO::PARAM_LOB to decimal(16, 0) is supported****

Testing decimal(16, 1):
****Conversion from PDO::PARAM_BOOL to decimal(16, 1) is supported****
****Conversion from PDO::PARAM_INT to decimal(16, 1) is supported****
****Conversion from PDO::PARAM_LOB to decimal(16, 1) is supported****

Testing decimal(16, 4):
****Conversion from PDO::PARAM_BOOL to decimal(16, 4) is supported****
****Conversion from PDO::PARAM_INT to decimal(16, 4) is supported****
****Conversion from PDO::PARAM_STR to decimal(16, 4) is supported****
****Conversion from PDO::PARAM_LOB to decimal(16, 4) is supported****

Testing decimal(16, 16):
****Conversion from PDO::PARAM_BOOL to decimal(16, 16) is supported****
****Conversion from PDO::PARAM_INT to decimal(16, 16) is supported****
****Conversion from PDO::PARAM_STR to decimal(16, 16) is supported****
****Conversion from PDO::PARAM_LOB to decimal(16, 16) is supported****

Testing decimal(38, 0):
****Conversion from PDO::PARAM_BOOL to decimal(38, 0) is supported****
****Conversion from PDO::PARAM_INT to decimal(38, 0) is supported****
****Conversion from PDO::PARAM_LOB to decimal(38, 0) is supported****

Testing decimal(38, 1):
****Conversion from PDO::PARAM_BOOL to decimal(38, 1) is supported****
****Conversion from PDO::PARAM_INT to decimal(38, 1) is supported****
****Conversion from PDO::PARAM_LOB to decimal(38, 1) is supported****

Testing decimal(38, 4):
****Conversion from PDO::PARAM_BOOL to decimal(38, 4) is supported****
****Conversion from PDO::PARAM_INT to decimal(38, 4) is supported****
****Conversion from PDO::PARAM_LOB to decimal(38, 4) is supported****

Testing decimal(38, 16):
****Conversion from PDO::PARAM_BOOL to decimal(38, 16) is supported****
****Conversion from PDO::PARAM_INT to decimal(38, 16) is supported****
****Conversion from PDO::PARAM_LOB to decimal(38, 16) is supported****

Testing decimal(38, 38):
****Conversion from PDO::PARAM_BOOL to decimal(38, 38) is supported****
****Conversion from PDO::PARAM_INT to decimal(38, 38) is supported****
****Conversion from PDO::PARAM_STR to decimal(38, 38) is supported****
****Conversion from PDO::PARAM_LOB to decimal(38, 38) is supported****

Testing numeric(1, 0):
****Conversion from PDO::PARAM_BOOL to numeric(1, 0) is supported****
****Conversion from PDO::PARAM_INT to numeric(1, 0) is supported****
****Conversion from PDO::PARAM_STR to numeric(1, 0) is supported****
****Conversion from PDO::PARAM_LOB to numeric(1, 0) is supported****

Testing numeric(1, 1):
****Conversion from PDO::PARAM_BOOL to numeric(1, 1) is supported****
****Conversion from PDO::PARAM_INT to numeric(1, 1) is supported****
****Conversion from PDO::PARAM_STR to numeric(1, 1) is supported****
****Conversion from PDO::PARAM_LOB to numeric(1, 1) is supported****

Testing numeric(4, 0):
****Conversion from PDO::PARAM_BOOL to numeric(4, 0) is supported****
****Conversion from PDO::PARAM_INT to numeric(4, 0) is supported****
****Conversion from PDO::PARAM_STR to numeric(4, 0) is supported****
****Conversion from PDO::PARAM_LOB to numeric(4, 0) is supported****

Testing numeric(4, 1):
****Conversion from PDO::PARAM_BOOL to numeric(4, 1) is supported****
****Conversion from PDO::PARAM_INT to numeric(4, 1) is supported****
****Conversion from PDO::PARAM_STR to numeric(4, 1) is supported****
****Conversion from PDO::PARAM_LOB to numeric(4, 1) is supported****

Testing numeric(4, 4):
****Conversion from PDO::PARAM_BOOL to numeric(4, 4) is supported****
****Conversion from PDO::PARAM_INT to numeric(4, 4) is supported****
****Conversion from PDO::PARAM_STR to numeric(4, 4) is supported****
****Conversion from PDO::PARAM_LOB to numeric(4, 4) is supported****

Testing numeric(16, 0):
****Conversion from PDO::PARAM_BOOL to numeric(16, 0) is supported****
****Conversion from PDO::PARAM_INT to numeric(16, 0) is supported****
****Conversion from PDO::PARAM_LOB to numeric(16, 0) is supported****

Testing numeric(16, 1):
****Conversion from PDO::PARAM_BOOL to numeric(16, 1) is supported****
****Conversion from PDO::PARAM_INT to numeric(16, 1) is supported****
****Conversion from PDO::PARAM_LOB to numeric(16, 1) is supported****

Testing numeric(16, 4):
****Conversion from PDO::PARAM_BOOL to numeric(16, 4) is supported****
****Conversion from PDO::PARAM_INT to numeric(16, 4) is supported****
****Conversion from PDO::PARAM_STR to numeric(16, 4) is supported****
****Conversion from PDO::PARAM_LOB to numeric(16, 4) is supported****

Testing numeric(16, 16):
****Conversion from PDO::PARAM_BOOL to numeric(16, 16) is supported****
****Conversion from PDO::PARAM_INT to numeric(16, 16) is supported****
****Conversion from PDO::PARAM_STR to numeric(16, 16) is supported****
****Conversion from PDO::PARAM_LOB to numeric(16, 16) is supported****

Testing numeric(38, 0):
****Conversion from PDO::PARAM_BOOL to numeric(38, 0) is supported****
****Conversion from PDO::PARAM_INT to numeric(38, 0) is supported****
****Conversion from PDO::PARAM_LOB to numeric(38, 0) is supported****

Testing numeric(38, 1):
****Conversion from PDO::PARAM_BOOL to numeric(38, 1) is supported****
****Conversion from PDO::PARAM_INT to numeric(38, 1) is supported****
****Conversion from PDO::PARAM_LOB to numeric(38, 1) is supported****

Testing numeric(38, 4):
****Conversion from PDO::PARAM_BOOL to numeric(38, 4) is supported****
****Conversion from PDO::PARAM_INT to numeric(38, 4) is supported****
****Conversion from PDO::PARAM_LOB to numeric(38, 4) is supported****

Testing numeric(38, 16):
****Conversion from PDO::PARAM_BOOL to numeric(38, 16) is supported****
****Conversion from PDO::PARAM_INT to numeric(38, 16) is supported****
****Conversion from PDO::PARAM_LOB to numeric(38, 16) is supported****

Testing numeric(38, 38):
****Conversion from PDO::PARAM_BOOL to numeric(38, 38) is supported****
****Conversion from PDO::PARAM_INT to numeric(38, 38) is supported****
****Conversion from PDO::PARAM_STR to numeric(38, 38) is supported****
****Conversion from PDO::PARAM_LOB to numeric(38, 38) is supported****