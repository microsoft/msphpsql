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
require_once("AEData.inc");

$dataTypes = array("binary", "varbinary", "varbinary(max)");
$lengths = array(1, 8, 64, 512, 4000);
$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.", "07006" => "Restricted data type attribute violation");

try {
    $conn = connect();
    $tbname = "test_binary_types";
    $spname = "test_binary_proc";

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
            $input0 = str_repeat("d", $length);
            $input1 = str_repeat("r", $length);
            insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $input0, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                                            "c_rand" => new BindParamOp(2, $input1, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY")), "prepareBindParam");
                
            // fetch with PDO::bindParam using a stored procedure
            dropProc($conn, $spname);
            $spSql = "CREATE PROCEDURE $spname (
                            @c_det $type OUTPUT, @c_rand $type OUTPUT ) AS
                            SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
            $conn->query($spSql);
            
            // call stored  procedure
            $outSql = getCallProcSqlPlaceholders($spname, 2);
            foreach ($pdoParamTypes as $pdoParamType) {
                $det = "";
                $rand = "";
                $stmt = $conn->prepare($outSql);
                trace("\nParam $pdoParamType:\n");
                if ($pdoParamType == "PDO::PARAM_STR") {
                    $stmt->bindParam(1, $det, PDO::PARAM_STR, $length, PDO::SQLSRV_ENCODING_BINARY); 
                    $stmt->bindParam(2, $rand, PDO::PARAM_STR, $length, PDO::SQLSRV_ENCODING_BINARY); 
                } elseif ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT") {
                    $stmt->bindParam(1, $det, constant($pdoParamType), PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
                    $stmt->bindParam(2, $rand, constant($pdoParamType), PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE);
                } else {
                    $stmt->bindParam(1, $det, constant($pdoParamType), $length); 
                    $stmt->bindParam(2, $rand, constant($pdoParamType), $length);
                }
                try {
                    $stmt->execute();
                    if ($det !== $input0 || $rand !== $input1) {
                        var_dump($det);
                        var_dump($rand);
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
                    } elseif ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT") {
                        // Expected error 07006: "Restricted data type attribute violation"
                        // What does this error mean? 
                        // The data value returned for a parameter bound as 
                        // SQL_PARAM_INPUT_OUTPUT or SQL_PARAM_OUTPUT could not 
                        // be converted to the data type identified by the  
                        // ValueType argument in SQLBindParameter.
                        $found = strpos($message, $errors['07006']);
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
    $tbname = "test_binary_types";
    $spname = "test_binary_proc";
    $conn = connect();
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
Done