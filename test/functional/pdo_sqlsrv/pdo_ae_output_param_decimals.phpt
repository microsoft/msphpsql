--TEST--
Test for retrieving encrypted data of decimals/numerics as output parameters
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$dataTypes = array("decimal", "numeric");
$precisions = array(1 => array(0, 1), 
                    4 => array(0, 1, 4),
                    16 => array(0, 1, 4, 16),
                    38 => array(0, 1, 4, 16, 38));
$inputValuesInit = array(92233720368547758089223372036854775808, -92233720368547758089223372036854775808);
$inputPrecision = 38;

$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.");

$pdoParamTypes = array(
    PDO::PARAM_BOOL,    // 5
    PDO::PARAM_NULL,    // 0
    PDO::PARAM_INT,     // 1 
    PDO::PARAM_STR,     // 2
    PDO::PARAM_LOB      // 3
);

function printValues($msg, $det, $rand, $inputValues)
{
    echo $msg;
    echo "input 0: "; var_dump($inputValues[0]);
    echo "fetched: "; var_dump($det);
    echo "input 1: "; var_dump($inputValues[1]);
    echo "fetched: "; var_dump($rand);
}

// function compareIntegers() returns false when the fetched values 
// are different from the expected inputs 
function compareIntegers($det, $rand, $inputValues, $pdoParamType)
{
    ///////////////////////////////////////////////////////////////////////
    // Assume $pdoParamType is PDO::PARAM_BOOL or PDO::PARAM_INT
    if (is_string($det)) {
        return (compareFloats($inputValues[0], floatval($det)) 
                && compareFloats($inputValues[1], floatval($rand)));
    } else {
        // if $pdoParamType is PDO::PARAM_BOOL, expect bool(true) or bool(false) 
        // depending on the rounded input values
        $input0 = floor($inputValues[0]); // the positive float
        $input1 = ceil($inputValues[1]); // the negative float
        
        return ($det == boolval($input0) && $rand == boolval($input1));
    }
}

// function compareDecimals() returns false when the fetched values 
// are different from the inputs, based on precision, scale 
function compareDecimals($det, $rand, $inputValues, $pdoParamType, $precision, $scale) 
{
    // Assume $pdoParamType is PDO::PARAM_STR
    for ($i = 0; $i < 2; $i++) {
        $inputStr = strval($inputValues[$i]);
        $fetchedStr = ($i == 0) ? strval(floatval($det)) : strval(floatval($rand));
        
        if ($precision == $scale) {
            // compare up to $precision + digits left if radix point ('.') + 
            // 1 digit ('.') + possibly the negative sign
            $len = $precision + 2 + $i;
        } elseif ($scale > 0) {
            // compare up to $precision + 1 digit ('.') 
            // + possibly the negative sign
            $len = $precision + 1 + $i;
        } else {
            // in this case, $scale = 0 
            // compare up to $precision + possibly the negative sign
            $len = $precision + $i;
        }
        
        trace("Comparing $len...");
        $result = substr_compare($inputStr, $fetchedStr, 0, $len);
        if ($result != 0) {
            return false;
        }
    }
    return true;
}

function testOutputDecimals($inout) 
{
    global $pdoParamTypes, $dataTypes, $inputValuesInit, $precisions, $inputPrecision, $errors;
    
    try {
        $conn = connect();
        
        $tbname = "test_decimals_types";
        $spname = "test_decimals_proc";

        foreach ($dataTypes as $dataType) {
            foreach ($precisions as $precision => $scales) {
                foreach ($scales as $scale) {
                    // construct the input values depending on the precision and scale
                    $precDiff = $inputPrecision - ($precision - $scale);
                    $inputValues = $inputValuesInit;
                    foreach ($inputValues as &$inputValue) {
                        $inputValue = $inputValue / pow(10, $precDiff);
                    }
                    
                    $type = "$dataType($precision, $scale)";
                    trace("\nTesting $type:\n");
                    
                    //create and populate table
                    $colMetaArr = array(new ColumnMeta($type, "c_det"), new ColumnMeta($type, "c_rand", null, "randomized"));
                    createTable($conn, $tbname, $colMetaArr);

                    $stmt = insertRow($conn, $tbname, array("c_det" => $inputValues[0], "c_rand" => $inputValues[1]));
                    
                    // fetch with PDO::bindParam using a stored procedure
                    $procArgs = "@c_det $type OUTPUT, @c_rand $type OUTPUT";
                    $procCode = "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
                    createProc($conn, $spname, $procArgs, $procCode);
            
                    // call stored procedure
                    $outSql = getCallProcSqlPlaceholders($spname, 2);
                    foreach ($pdoParamTypes as $pdoParamType) {
                        // Do not initialize $det or $rand as empty strings 
                        // See VSO 2915 for details. The string must be a numeric
                        // string, and to make it work for all precisions, we 
                        // simply set it to a single-digit string.
                        $det = $rand = '0';
                        $stmt = $conn->prepare($outSql);
                    
                        $len = 2048;
                        if ($pdoParamType == PDO::PARAM_BOOL || $pdoParamType == PDO::PARAM_INT) {
                            $len = PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE;
                            $det = $rand = 0;
                        } 
                        
                        trace("\nParam $pdoParamType with INOUT = $inout\n");
                        if ($inout) {
                            $paramType = $pdoParamType | PDO::PARAM_INPUT_OUTPUT;
                        } else {
                            $paramType = $pdoParamType;
                        }
                        
                        $stmt->bindParam(1, $det, $paramType, $len); 
                        $stmt->bindParam(2, $rand, $paramType, $len); 
                
                        try {
                            $stmt->execute();
                            
                            $errMsg = "****$type as $pdoParamType failed with INOUT = $inout:****\n";
                            if ($pdoParamType == PDO::PARAM_BOOL || $pdoParamType == PDO::PARAM_INT) {
                                if (!compareIntegers($det, $rand, $inputValues, $pdoParamType)) {
                                    printValues($errMsg, $det, $rand, $inputValues);
                                }
                            } else {
                                // When $pdoParamType is PDO::PARAM_STR, the accuracies
                                // should have been preserved based on the original
                                // precision and scale, so compare the retrieved values 
                                // against the input values with more details
                                if (!compareDecimals($det, $rand, $inputValues, $pdoParamType, $precision, $scale)) {
                                    printValues($errMsg, $det, $rand, $inputValues);
                                }
                            }
                        } catch (PDOException $e) {
                            $message = $e->getMessage();
                            $errMsg = "EXCEPTION: ****$type as $pdoParamType failed with INOUT = $inout:****\n";
                            if ($pdoParamType == PDO::PARAM_NULL || $pdoParamType == PDO::PARAM_LOB) {
                                // Expected error IMSSP: "An invalid PHP type was specified 
                                // as an output parameter. DateTime objects, NULL values, and
                                // streams cannot be specified as output parameters."
                                $found = strpos($message, $errors['IMSSP']);
                                if ($found === false) {
                                    printValues($errMsg, $det, $rand, $inputValues);
                                }
                            } elseif ($precision >= 16) {
                                // Large numbers are expected to fail when 
                                // converting to booleans / integers
                                if (isAEConnected()) {
                                    $error = "Error converting a double (value out of range) to an integer";
                                } else {
                                    $error = "Error converting data type $dataType to int"; 
                                }
                                $found = strpos($message, $error);
                                if ($found === false) {
                                    printValues($errMsg, $det, $rand, $inputValues);
                                }
                            } else {
                                printValues($errMsg, $det, $rand, $inputValues);
                            }
                        }
                    }
                    dropProc($conn, $spname);
                    dropTable($conn, $tbname);
                }
            }
        }
        unset($stmt); 
        unset($conn);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}

testOutputDecimals(false);
testOutputDecimals(true);

echo "Done\n";

?>
--CLEAN--
<?php
    // drop the temporary table and stored procedure in case 
    // the test failed without dropping them
    require_once("MsCommon_mid-refactor.inc");
    $tbname = "test_decimals_types";
    $spname = "test_decimals_proc";
    $conn = connect();
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
Done