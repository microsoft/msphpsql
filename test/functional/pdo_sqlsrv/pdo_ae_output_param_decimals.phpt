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
require_once("AEData.inc");

$dataTypes = array("decimal", "numeric");
$precisions = array(1 => array(0, 1), 
                    4 => array(0, 1, 4),
                    16 => array(0, 1, 4, 16),
                    38 => array(0, 1, 4, 16, 38));
$inputValuesInit = array(92233720368547758089223372036854775808, -92233720368547758089223372036854775808);
$inputPrecision = 38;

$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.");

function printValues($msg, $det, $rand, $inputValues)
{
    echo $msg;
    echo "input 0: "; var_dump($inputValues[0]);
    echo "fetched: "; var_dump($det);
    echo "input 1: "; var_dump($inputValues[1]);
    echo "fetched: "; var_dump($rand);
}

// function compareDecimals() returns false when the fetched values 
// are different from the inputs, based on precision, scale and PDO Param Type
function compareDecimals($det, $rand, $inputValues, $pdoParamType, $precision, $scale) 
{
    if (!isAEConnected() && ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT")) {
        // Without AE, decimals or numerics are fetched as BOOL / INT will be integers
        // Large numbers will fail to be connected, but the exception would have been
        // caught
        $input0 = floor($inputValues[0]); // the positive float
        $input1 = ceil($inputValues[1]); // the negative float
        if ($det != $input0 || $rand != $input1) {
            return false;
        }
        return true;
    }
    
    for ($i = 0; $i < 2; $i++) {
        $inputStr = strval($inputValues[$i]);

        if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT") {
            $fetchedStr = ($i == 0) ? strval($det) : strval($rand);
        } elseif ($pdoParamType == "PDO::PARAM_STR") {
            // if decimals are fetched as strings, the zeroes before the radix point
            // are dropped - convert it to float then back to string
            $fetchedStr = ($i == 0) ? strval(floatval($det)) : strval(floatval($rand));
        }
        
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

                $stmt = insertRow($conn, $tbname, array("c_det" => $inputValues[0], "c_rand" => $inputValues[1] ), null, $r);
                
                // fetch with PDO::bindParam using a stored procedure
                dropProc($conn, $spname);
                $spSql = "CREATE PROCEDURE $spname (
                                @c_det $type OUTPUT, @c_rand $type OUTPUT ) AS
                                SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
                $conn->query($spSql);
        
                // call stored procedure
                $outSql = getCallProcSqlPlaceholders($spname, 2);
                foreach ($pdoParamTypes as $pdoParamType) {
                    $det = 0.0;
                    $rand = 0.0;
                    $stmt = $conn->prepare($outSql);
                
                    $len = 2048;
                    if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT") {
                        $len = PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE;
                    } 
                    trace("\nParam $pdoParamType with $len\n");
                
                    $stmt->bindParam(1, $det, constant($pdoParamType), $len); 
                    $stmt->bindParam(2, $rand, constant($pdoParamType), $len); 
            
                    try {
                        $stmt->execute();
                        if (traceMode()) {
                            $msg = "****For debugging -- $type as $pdoParamType ****\n";
                            printValues($msg, $det, $rand, $inputValues);
                        }

                        // Compare the retrieved values against the input values:
                        // if very different, print them all
                        if (!compareDecimals($det, $rand, $inputValues, $pdoParamType, $precision, $scale)) {
                            $msg = "****$type as $pdoParamType failed:****\n";
                            printValues($msg, $det, $rand, $inputValues);
                        }
                    } catch (PDOException $e) {
                        $message = $e->getMessage();
                        if ($pdoParamType == "PDO::PARAM_NULL" || $pdoParamType == "PDO::PARAM_LOB") {
                            // Expected error IMSSP: "An invalid PHP type was specified 
                            // as an output parameter. DateTime objects, NULL values, and
                            // streams cannot be specified as output parameters."
                            $found = strpos($message, $errors['IMSSP']);
                            if ($found === false) {
                                echo "****$pdoParamType failed:\n$message****\n";
                            }
                        } elseif (!isAEConnected() && ($precision >= 16) && ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT")) {
                            // When not AE enabled, large numbers are expected to 
                            // fail when converting to integers
                            $msg = "Error converting data type $dataType to int"; 
                            $found = strpos($message, $msg);
                            if ($found === false) {
                                $msg = "****$type as $pdoParamType failed:****\n";
                                printValues($msg, $det, $rand, $inputValues);
                            }
                        } else {
                            echo("****$pdoParamType failed:\n$message****\n");
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