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
require_once("AEData.inc");

$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.");

// this function returns true if the floats are more different than expected
function compareFloats($actual, $expected) 
{
    $epsilon = 0.00001;
    $diff = abs(($actual - $expected) / $expected);
    return ($diff > $epsilon);
}

function testOutputFloats($fetchNumeric)
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
                if (TraceMode()) {
                    echo "input: "; var_dump($inputValues[$i]);
                }
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
                    // Compare the retrieved values against the input values
                    // if either of them is very different, print them all
                    if (compareFloats(floatval($det), $inputValues[0]) || 
                        compareFloats(floatval($rand), $inputValues[1])) {
                        echo "****$type as $pdoParamType failed:****\n";
                        echo "input 0: "; var_dump($inputValues[0]);
                        echo "fetched: "; var_dump($det);
                        echo "input 1: "; var_dump($inputValues[1]);
                        echo "fetched: "; var_dump($rand);
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
    } catch (PDOException $e) {
        echo $e->getMessage();
    }
}

testOutputFloats(false);
testOutputFloats(true);

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