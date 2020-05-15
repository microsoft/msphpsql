--TEST--
Test for retrieving encrypted data of floats as output parameters
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.");

$pdoParamTypes = array(
    PDO::PARAM_BOOL,    // 5
    PDO::PARAM_NULL,    // 0
    PDO::PARAM_INT,     // 1 
    PDO::PARAM_STR,     // 2
    PDO::PARAM_LOB      // 3
);

//////////////////////////////////////////////////////////////////////////////////

function printValues($msg, $det, $rand, $inputValues)
{
    echo $msg;
    echo "input 0: "; var_dump($inputValues[0]);
    echo "fetched: "; var_dump($det);
    echo "input 1: "; var_dump($inputValues[1]);
    echo "fetched: "; var_dump($rand);
}

function testOutputFloats($fetchNumeric, $inout)
{
    global $pdoParamTypes, $inputValues, $errors;
    
    try {
        $conn = connect();
        $conn->setAttribute(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE, $fetchNumeric);

        $tbname = "test_floats_types";
        $spname = "test_floats_proc";

        $bits = array(1, 12, 24, 36, 53);
        
        foreach ($bits as $bit) {
            $type = "float($bit)";
            trace("\nTesting $type:\n");

            $inputValues = array();
            // create random input values
            for ($i = 0; $i < 2; $i++) {
                $mantissa = rand(1, 100000000);
                $decimals = rand(1, 100000000);
                $floatNum = $mantissa + $decimals / 10000000;
                if ($i > 0) {
                    // make the second input negative
                    $floatNum *= -1;
                }
                array_push($inputValues, $floatNum);
            }
            //create and populate table
            $colMetaArr = array(new ColumnMeta($type, "c_det"), new ColumnMeta($type, "c_rand", null, "randomized"));
            createTable($conn, $tbname, $colMetaArr);
            insertRow($conn, 
                      $tbname, 
                      array("c_det" => new BindParamOp(1, $inputValues[0], 'PDO::PARAM_INT'),
                            "c_rand" => new BindParamOp(2, $inputValues[1], 'PDO::PARAM_INT')),
                      "prepareBindParam");
                
            // fetch with PDO::bindParam using a stored procedure
            $procArgs = "@c_det $type OUTPUT, @c_rand $type OUTPUT";
            $procCode = "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
            createProc($conn, $spname, $procArgs, $procCode);
            
            // call stored procedure
            $outSql = getCallProcSqlPlaceholders($spname, 2);
            foreach ($pdoParamTypes as $pdoParamType) {
                $det = 0.0;
                $rand = 0.0;
                $stmt = $conn->prepare($outSql);
                
                $len = 2048;
                if ($pdoParamType == PDO::PARAM_BOOL || $pdoParamType == PDO::PARAM_INT) {
                    $len = PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE;
                    $det = 0;
                    $rand = 0;
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
                    if ($pdoParamType == PDO::PARAM_BOOL) {
                        // for boolean values, they should all be bool(true)
                        // because all floats are non-zeroes
                        if (!$det || !$rand) {
                            printValues($errMsg, $det, $rand, $inputValues);
                        }
                    } else {
                        // Compare the retrieved values against the input values
                        // if either of them is very different, print them all
                        if (!compareFloats($inputValues[0], floatval($det)) || 
                            !compareFloats($inputValues[1], floatval($rand))) {
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
                    } else {
                        printValues($errMsg, $det, $rand, $inputValues);
                    }
                }
            }
            dropProc($conn, $spname);
            dropTable($conn, $tbname);
        }
        unset($stmt);
        unset($conn);
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}

testOutputFloats(false, false);
testOutputFloats(true, false);
testOutputFloats(false, true);
testOutputFloats(true, true);

echo "Done\n";

?>
--CLEAN--
<?php
    // drop the temporary table and stored procedure in case 
    // the test failed without dropping them
    require_once("MsCommon_mid-refactor.inc");
    $tbname = "test_floats_types";
    $spname = "test_floats_proc";
    $conn = connect();
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
Done