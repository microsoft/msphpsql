--TEST--
Test for retrieving encrypted data of binary types of various sizes as output parameters
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$dataTypes = array("binary", "varbinary", "varbinary(max)");
$lengths = array(1, 2, 4, 8, 64, 512, 4000);
$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.", "07006" => "Restricted data type attribute violation");

$pdoParamTypes = array(
    PDO::PARAM_BOOL,    // 5
    PDO::PARAM_NULL,    // 0
    PDO::PARAM_INT,     // 1 
    PDO::PARAM_STR,     // 2
    PDO::PARAM_LOB      // 3
);

//////////////////////////////////////////////////////////////////////////////////
function printValues($msg, $det, $rand, $input0, $input1)
{
    echo $msg;
    echo "input 0: "; var_dump($input0); 
    echo "fetched: "; var_dump($det); 
    echo "input 1: "; var_dump($input1);
    echo "fetched: "; var_dump($rand); 
}

function convert2Hex($ch, $length)
{
    // Without AE, the binary values returned as integers will 
    // have lengths no more than 4 times the original hex string value 
    // (e.g. string(8) "65656565") - limited by the buffer sizes
    if (!isAEConnected()) {
        $count = ($length <= 2) ? $length : 4;
    } else {
        $count = $length;
    }
    
    return str_repeat(bin2hex($ch), $count);
}

function testOutputBinary($inout)
{
    global $pdoParamTypes, $dataTypes, $lengths, $errors;
    
    try {
        $conn = connect();
        $tbname = "test_binary_types";
        $spname = "test_binary_proc";
        $ch0 = 'd';
        $ch1 = 'e';

        foreach ($dataTypes as $dataType) {
            $maxtype = strpos($dataType, "(max)");
            foreach ($lengths as $length) {
                if ($maxtype !== false) {
                    $type = $dataType;
                } else {
                    $type = "$dataType($length)";
                }
                trace("\nTesting $type:\n");
                    
                //create and populate table
                $colMetaArr = array(new ColumnMeta($type, "c_det"), new ColumnMeta($type, "c_rand", null, "randomized"));
                createTable($conn, $tbname, $colMetaArr);
                $input0 = str_repeat($ch0, $length);
                $input1 = str_repeat($ch1, $length);
                $ord0 = convert2Hex($ch0, $length);
                $ord1 = convert2Hex($ch1, $length);
                insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $input0, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                                                "c_rand" => new BindParamOp(2, $input1, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY")), "prepareBindParam");
                    
                // fetch with PDO::bindParam using a stored procedure
                $procArgs = "@c_det $type OUTPUT, @c_rand $type OUTPUT";
                $procCode = "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
                createProc($conn, $spname, $procArgs, $procCode);
                
                // call stored  procedure
                $outSql = getCallProcSqlPlaceholders($spname, 2);
                foreach ($pdoParamTypes as $pdoParamType) {
                    $stmt = $conn->prepare($outSql);
                    trace("\nParam $pdoParamType with INOUT = $inout\n");
                    
                    if ($inout && $pdoParamType != PDO::PARAM_STR) {
                        // Currently do not support getting binary as strings + INOUT param
                        // See VSO 2829 for details
                        $paramType = $pdoParamType | PDO::PARAM_INPUT_OUTPUT;
                    } else {
                        $paramType = $pdoParamType;
                    }

                    $det = "";
                    $rand = "";
                    if ($pdoParamType == PDO::PARAM_STR || $pdoParamType == PDO::PARAM_LOB) {
                        $stmt->bindParam(1, $det, $paramType, $length, PDO::SQLSRV_ENCODING_BINARY); 
                        $stmt->bindParam(2, $rand, $paramType, $length, PDO::SQLSRV_ENCODING_BINARY); 
                    } elseif ($pdoParamType == PDO::PARAM_BOOL || $pdoParamType == PDO::PARAM_INT) {
                        $det = $rand = 0;
                        $stmt->bindParam(1, $det, $paramType, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
                        $stmt->bindParam(2, $rand, $paramType, PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
                    } else {
                        $stmt->bindParam(1, $det, $paramType, $length); 
                        $stmt->bindParam(2, $rand, $paramType, $length);
                    }
                    
                    try {
                        $stmt->execute();

                        $errMsg = "****$dataType as $pdoParamType failed with INOUT = $inout:****\n";
                        if ($pdoParamType == PDO::PARAM_STR) {
                            if ($det !== $input0 || $rand !== $input1) {
                                printValues($errMsg, $det, $rand, $input0, $input1);
                            }
                        } elseif ($pdoParamType == PDO::PARAM_BOOL) {
                            // for boolean values, they should all be bool(true)
                            // because all floats are non-zeroes
                            // This only occurs without AE
                            // With AE enabled, this would have caused an exception
                            if (!$det || !$rand) {
                                printValues($errMsg, $det, $rand, $input0, $input1);
                            }
                        } else {
                            // $pdoParamType is PDO::PARAM_INT
                            // This only occurs without AE -- likely a rare use case
                            // With AE enabled, this would have caused an exception
                            if (strval($det) != $ord0 || strval($rand) != $ord1) {
                                printValues($errMsg, $det, $rand, $ord0, $ord1);
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
                                printValues($errMsg, $det, $rand, $input0, $input1);
                            }
                        } elseif ($pdoParamType == PDO::PARAM_BOOL || PDO::PARAM_INT) { 
                            if (isAEConnected()) {
                                if ($pdoParamType == PDO::PARAM_INT) {
                                    // Expected to fail with this message
                                    $error = "String data, right truncated for output parameter";
                                    $found = strpos($message, $error);
                                } else {
                                    // PDO::PARAM_BOOL -
                                    // Expected error 07006 with AE enabled: 
                                    // "Restricted data type attribute violation"
                                    // The data value returned for a parameter bound as 
                                    // SQL_PARAM_INPUT_OUTPUT or SQL_PARAM_OUTPUT could not 
                                    // be converted to the data type identified by the  
                                    // ValueType argument in SQLBindParameter.
                                    $found = strpos($message, $errors['07006']);
                                }
                            } else {
                                // When not AE enabled, expected to fail with something like this message
                                // "Implicit conversion from data type nvarchar(max) to binary is not allowed. Use the CONVERT function to run this query."
                                // Sometimes it's about nvarchar too
                                $error = "to $dataType is not allowed. Use the CONVERT function to run this query."; 
                                $found = strpos($message, $error);
                            }
                            if ($found === false) {
                                printValues($errMsg, $det, $rand, $input0, $input1);
                            }
                        } else {
                            // catch all
                            printValues($errMsg, $det, $rand, $input0, $input1);
                        }
                    }
                }
                dropProc($conn, $spname);
                dropTable($conn, $tbname);
            }
        }
        unset($stmt);
        unset($conn);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}
    
testOutputBinary(false);
testOutputBinary(true);

echo "Done\n";
    
?>
--CLEAN--
<?php
    // drop the temporary table and stored procedure in case 
    // the test failed without dropping them
    require_once("MsCommon_mid-refactor.inc");
    $tbname = "test_binary_types";
    $spname = "test_binary_proc";
    $conn = connect();
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
Done