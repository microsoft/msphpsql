--TEST--
Test for retrieving encrypted data of datetimes as output parameters
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

$dataTypes = array("datetime2", "datetimeoffset", "time");
$precisions = array(/*0, */1, 2, 4, 7);
$inputValuesInit = array("datetime2" => array("0001-01-01 00:00:00", "9999-12-31 23:59:59"),
                     "datetimeoffset" => array("0001-01-01 00:00:00 -14:00", "9999-12-31 23:59:59 +14:00"),
                     "time" => array("00:00:00", "23:59:59"));

$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.", "07006" => "Restricted data type attribute violation");

$pdoParamTypes = array(
    PDO::PARAM_BOOL,    // 5
    PDO::PARAM_NULL,    // 0
    PDO::PARAM_INT,     // 1 
    PDO::PARAM_STR,     // 2
    PDO::PARAM_LOB      // 3
);

//////////////////////////////////////////////////////////////////////////////////

// compareDate() returns true when the date/time values are basically the same
// e.g. 00:00:00.000 is the same as 00:00:00
function compareDate($dtout, $dtin, $dataType) 
{
    if ($dataType == "datetimeoffset") {
        $dtarr = explode(' ', $dtin);
        if (strpos($dtout, $dtarr[0]) !== false && strpos($dtout, $dtarr[1]) !== false && strpos($dtout, $dtarr[2]) !== false) {
            return true;
        }
    } else {
        if (strpos($dtout, $dtin) !== false) {
            return true;
        }
    }
    return false;
}

function printValues($msg, $det, $rand, $inputValues)
{
    echo $msg;
    echo "input 0: "; var_dump($inputValues[0]);
    echo "fetched: "; var_dump($det);
    echo "input 1: "; var_dump($inputValues[1]);
    echo "fetched: "; var_dump($rand);
}

function testOutputDatetimes($inout)
{
    global $pdoParamTypes, $dataTypes, $precisions, $inputValuesInit, $errors;

    try {
        $conn = connect();
        
        $tbname = "test_datetimes_types";
        $spname = "test_datetimes_proc";

        foreach ($dataTypes as $dataType) {
            foreach ($precisions as $precision) {
                // change the input values depending on the precision
                $inputValues[0] = $inputValuesInit[$dataType][0];
                $inputValues[1] = $inputValuesInit[$dataType][1];
                if ($precision != 0) {
                    if ($dataType == "datetime2") {
                        $inputValues[1] .= "." . str_repeat("9", $precision);
                    } else if ($dataType == "datetimeoffset") {
                        $inputPieces = explode(" ", $inputValues[1]);
                        $inputValues[1] = $inputPieces[0] . " " . $inputPieces[1] . "." . str_repeat("9", $precision) . " " . $inputPieces[2];
                    } else if ($dataType == "time") {
                        $inputValues[0] .= "." . str_repeat("0", $precision);
                        $inputValues[1] .= "." . str_repeat("9", $precision);
                    }
                }

                $type = "$dataType($precision)";
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
                    $det = 0;
                    $rand = 0;
                    $stmt = $conn->prepare($outSql);
                    trace("\nParam $pdoParamType with INOUT = $inout\n");
                    
                    if ($inout) {
                        $paramType = $pdoParamType | PDO::PARAM_INPUT_OUTPUT;
                    } else {
                        $paramType = $pdoParamType;
                    }
                    
                    $len = 2048;
                    if ($pdoParamType == PDO::PARAM_BOOL || $pdoParamType == PDO::PARAM_INT) {
                        $len = PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE;
                    }

                    $stmt->bindParam(1, $det, $paramType, $len); 
                    $stmt->bindParam(2, $rand, $paramType, $len); 
                
                    try {
                        $stmt->execute();
                        $errMsg = "****$type as $pdoParamType failed with INOUT = $inout:****\n";
                        // What follows only happens with OUTPUT parameter
                        if ($inout) {
                            echo "Any datetime type as INOUT param should have caused an exception!\n";
                        }
                        if ($pdoParamType == PDO::PARAM_INT) {
                            // Expect an integer, the first part of the date time string
                            $ch = ($dataType == "time")? ':' : '-';
                            $tmp0 = explode($ch, $inputValues[0]);
                            $tmp1 = explode($ch, $inputValues[1]);
                            
                            if ($det != intval($tmp0[0]) || $rand != intval($tmp1[0])) {
                                printValues($errMsg, $det, $rand, $inputValues);
                            }
                        } elseif (!compareDate($det, $inputValues[0], $dataType) || 
                            !compareDate($rand, $inputValues[1], $dataType)) {
                            printValues($errMsg, $det, $rand, $inputValues);
                        }
                    } catch (PDOException $e) {
                        $message = $e->getMessage();
                        $errMsg = "EXCEPTION: ****$type as $pdoParamType failed with INOUT = $inout:\n$message****\n";
                        if ($pdoParamType == PDO::PARAM_NULL || $pdoParamType == PDO::PARAM_LOB) {
                            // Expected error IMSSP: "An invalid PHP type was specified 
                            // as an output parameter. DateTime objects, NULL values, and
                            // streams cannot be specified as output parameters."
                            $found = strpos($message, $errors['IMSSP']);
                        } elseif (isAEConnected()) {
                            if ($pdoParamType == PDO::PARAM_BOOL) {
                                // Expected error 07006: "Restricted data type attribute violation"
                                // What does this error mean? 
                                // The data value returned for a parameter bound as 
                                // SQL_PARAM_INPUT_OUTPUT or SQL_PARAM_OUTPUT could not 
                                // be converted to the data type identified by the  
                                // ValueType argument in SQLBindParameter.
                                $found = strpos($message, $errors['07006']);
                            } else {
                                $error = "Invalid character value for cast specification";
                                $found = strpos($message, $error);
                            }
                        } else {
                            if ($pdoParamType == PDO::PARAM_BOOL) {
                                $error = "Operand type clash: int is incompatible with $dataType"; 
                            } else {
                                $error = "Error converting data type nvarchar to $dataType";
                            }
                            $found = strpos($message, $error);
                        }
                        if ($found === false) {
                            printValues($errMsg, $det, $rand, $inputValues);
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

testOutputDatetimes(false);
testOutputDatetimes(true);

echo "Done\n";

?>
--CLEAN--
<?php
    // drop the temporary table and stored procedure in case 
    // the test failed without dropping them
    require_once("MsCommon_mid-refactor.inc");
    $tbname = "test_datetimes_types";
    $spname = "test_datetimes_proc";
    $conn = connect();
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
Done