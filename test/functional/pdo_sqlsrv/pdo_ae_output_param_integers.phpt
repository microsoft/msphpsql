--TEST--
Test for retrieving encrypted data of integral types as output parameters
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

$dataTypes = array("bit", "tinyint", "smallint", "int", "bigint");
$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.", "22003" => "Numeric value out of range", "42000" => "Error converting data type bigint to int");

function printValues($msg, $det, $rand, $inputValues)
{
    echo $msg;
    echo "input 0: "; var_dump($inputValues[0]);
    echo "fetched: "; var_dump($det);
    echo "input 1: "; var_dump($inputValues[1]);
    echo "fetched: "; var_dump($rand);
}

function generateInputs($dataType)
{
    // create random input values based on data types
    // make the second input negative but only for some data types
    if ($dataType == "bit") {
        $inputValues = array(0, 1);
    } elseif ($dataType == "tinyint") {
        $inputValues = array();
        for ($i = 0; $i < 2; $i++) {
            $randomNum = rand(0, 255);
            array_push($inputValues, $randomNum);
        }
    } else {
        switch ($dataType) {
            case "smallint":
                $max = 32767;
                break;
            case "int":
                $max = 2147483647;
                break;
            default:
                $max = getrandmax();
        }
        
        $inputValues = array();
        for ($i = 0; $i < 2; $i++) {
            $randomNum = rand(0, $max);
            if ($i > 0) {
                // make the second input negative but only for some data types
                $randomNum *= -1;
            }
            array_push($inputValues, $randomNum);
            if (traceMode()) {
                echo "input: "; var_dump($inputValues[$i]);
            }
        }
    }
    return $inputValues;
}

try {
    $conn = connect();
    $tbname = "test_integers_types";
    $spname = "test_integers_proc";
    
    foreach ($dataTypes as $dataType) {
        trace("\nTesting $dataType:\n");
            
        //create and populate table
        $colMetaArr = array(new ColumnMeta($dataType, "c_det"), new ColumnMeta($dataType, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);
        $inputValues = generateInputs($dataType);
        insertRow($conn, $tbname, array("c_det" => $inputValues[0],
                                        "c_rand" => $inputValues[1]));
            
        // fetch with PDO::bindParam using a stored procedure
        dropProc($conn, $spname);
        $spSql = "CREATE PROCEDURE $spname (
                        @c_det $dataType OUTPUT, @c_rand $dataType OUTPUT ) AS
                        SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
        $conn->query($spSql);
        
        // call stored procedure
        $outSql = getCallProcSqlPlaceholders($spname, 2);
        foreach ($pdoParamTypes as $pdoParamType) {
            $det = 0;
            $rand = 0;
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
                $errorMsg = "****$dataType as $pdoParamType failed:****\n";
                if ($pdoParamType == "PDO::PARAM_STR") {
                    if ($det !== strval($inputValues[0]) || $rand !== strval($inputValues[1])) {
                        // comparisons between strings, use '!=='
                        printValues($errorMsg, $det, $rand, $inputValues);
                    }
                } elseif ($pdoParamType == "PDO::PARAM_INT" || $pdoParamType == "PDO::PARAM_BOOL") {
                    // comparisons between integers and booleans, do not use '!=='
                    if ($det != $inputValues[0] || $rand != $inputValues[1]) {
                        printValues($errorMsg, $det, $rand, $inputValues);
                    }
                } else {
                    printValues($errorMsg, $det, $rand, $inputValues);
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
                } elseif ($dataType == "bigint" && ($pdoParamType == "PDO::PARAM_INT" || $pdoParamType == "PDO::PARAM_BOOL")) {
                    if (isAEConnected()) {
                        // Expected error 22003: "Numeric value out of range"
                        // This is expected when converting big integer to integer or bool
                        $found = strpos($message, $errors['22003']);
                    } elseif ($pdoParamType == "PDO::PARAM_BOOL") {
                        // Expected error 42000: "Error converting data type bigint to int"
                        // This is expected when not AE connected and converting big integer to bool
                        $found = strpos($message, $errors['42000']);
                    }
                    if ($found === false) {
                        echo "****$pdoParamType failed:\n$message****\n";
                    }
                } else {
                    echo("****$pdoParamType failed:\n$message****\n");
                }
            }
        }
        dropProc($conn, $spname);
        dropTable($conn, $tbname);
    }
    unset($stmt);
    unset($conn);
    echo "Done\n";
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
--CLEAN--
<?php
    // drop the temporary table and stored procedure in case 
    // the test failed without dropping them
    require_once("MsCommon_mid-refactor.inc");
    $tbname = "test_integers_types";
    $spname = "test_integers_proc";
    $conn = connect();
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
Done