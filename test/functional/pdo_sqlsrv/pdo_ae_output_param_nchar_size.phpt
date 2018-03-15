--TEST--
Test for retrieving encrypted data of nchar types of various sizes as output parameters
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
Note: Because the maximum allowable table row size is 8060 bytes, 7 bytes of which are reserved for internal overhead. In other words, this allows up to two nvarchar() columns with length slightly 
more than 2000 wide characters. Therefore, the max length in this test is 2010.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$dataTypes = array("nchar", "nvarchar", "nvarchar(max)");
$lengths = array(1, 8, 64, 512, 2010);
$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.", "22003" => "Numeric value out of range");

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

function testOutputNChars($inout)
{
    global $pdoParamTypes, $dataTypes, $lengths, $errors;

    try {
        $conn = connect();
        $tbname = "test_nchar_types";
        $spname = "test_nchar_proc";
        
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
                $input0 = str_repeat("1", $length);
                $input1 = str_repeat("2", $length);
                insertRow($conn, $tbname, array("c_det" => $input0,
                                                "c_rand" => $input1));
                    
                // fetch with PDO::bindParam using a stored procedure
                $procArgs = "@c_det $type OUTPUT, @c_rand $type OUTPUT";
                $procCode = "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
                createProc($conn, $spname, $procArgs, $procCode);
                
                // call stored procedure
                $outSql = getCallProcSqlPlaceholders($spname, 2);
                foreach ($pdoParamTypes as $pdoParamType) {
                    $det = "";
                    $rand = "";
                    $stmt = $conn->prepare($outSql);
                    trace("\nParam $pdoParamType with INOUT = $inout\n");
                    
                    if ($inout) {
                        $paramType = $pdoParamType | PDO::PARAM_INPUT_OUTPUT;
                    } else {
                        $paramType = $pdoParamType;
                    }

                    $len = $length;
                    if ($pdoParamType == PDO::PARAM_BOOL || $pdoParamType == PDO::PARAM_INT) {
                        $len = PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE;
                        $det = $rand = 0;
                    }
                    
                    $stmt->bindParam(1, $det, $paramType, $len); 
                    $stmt->bindParam(2, $rand, $paramType, $len); 
                    
                    try {
                        $stmt->execute();
                        $errMsg = "****$type as $pdoParamType failed with INOUT = $inout:****\n";
                        // When $length >= 64, a string is returned regardless of $pdoParamType
                        if ($length < 64 && $pdoParamType != PDO::PARAM_STR) {
                            if ($pdoParamType == PDO::PARAM_BOOL) {
                                // For boolean values, they should all be bool(true)
                                // because all "string literals" are non-zeroes
                                if (!$det || !$rand) {
                                    printValues($errMsg, $det, $rand, $input0, $input1);
                                }
                            } else {
                                // $pdoParamType = PDO::PARAM_INT
                                // Expect numeric values
                                if ($det != intval($input0) || $rand != intval($input1)) {
                                    printValues($errMsg, $det, $rand, $input0, $input1);
                                }
                            }
                        } elseif ($det !== $input0 || $rand !== $input1) {
                            printValues($errMsg, $det, $rand, $input0, $input1);
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
                        } elseif ($pdoParamType == PDO::PARAM_BOOL) {
                            if (isAEConnected()) {
                                // Expected error 22003: "Numeric value out of range"
                                $found = strpos($message, $errors['22003']);
                            } else {
                                // When not AE enabled, expected to fail to convert 
                                // whatever char type to integers
                                $error = "Error converting data type $dataType to int"; 
                                $found = strpos($message, $error);                            
                            }
                            if ($found === false) {
                                printValues($errMsg, $det, $rand, $input0, $input1);
                            }
                        } else {
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

testOutputNChars(false);
testOutputNChars(true);

echo "Done\n";    

?>
--CLEAN--
<?php
    // drop the temporary table and stored procedure in case 
    // the test failed without dropping them
    require_once("MsCommon_mid-refactor.inc");
    $tbname = "test_nchar_types";
    $spname = "test_nchar_proc";
    $conn = connect();
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
Done