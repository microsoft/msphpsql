--TEST--
Test for retrieving encrypted data of nchar types of various sizes as output parameters
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
Note: Because the maximum allowable table row size is 8060 bytes, 7 bytes of which are reservedinternal for internal overhead. In other words, this allows up to two nvarchar() columns with length slightly 
more than 2000 wide characters. Therefore, the max length in this test is 2010.
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array("nchar", "nvarchar", "nvarchar(max)");
$lengths = array(1, 8, 64, 512, 2010);
$errors = array("IMSSP" => "An invalid PHP type was specified as an output parameter. DateTime objects, NULL values, and streams cannot be specified as output parameters.", "22018" => "Invalid character value for cast specification");

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
            $input0 = str_repeat("d", $length);
            $input1 = str_repeat("r", $length);
            insertRow($conn, $tbname, array("c_det" => $input0,
                                            "c_rand" => $input1));
                
            // fetch with PDO::bindParam using a stored procedure
            dropProc($conn, $spname);
            $spSql = "CREATE PROCEDURE $spname (
                            @c_det $type OUTPUT, @c_rand $type OUTPUT ) AS
                            SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname";
            $conn->query($spSql);
            
            // call stored procedure
            $outSql = getCallProcSqlPlaceholders($spname, 2);
            foreach ($pdoParamTypes as $pdoParamType) {
                $det = "";
                $rand = "";
                $stmt = $conn->prepare($outSql);
                trace("\nParam $pdoParamType:\n");
                
                $len = $length;
                if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT") {
                    $len = PDO::SQLSRV_PARAM_OUT_DEFAULT_SIZE;
                }
                
                $stmt->bindParam(1, $det, constant($pdoParamType), $len); 
                $stmt->bindParam(2, $rand, constant($pdoParamType), $len); 
                
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
                        if (isAEConnected()) {
                            // Expected error 22018: "Invalid character value for 
                            // cast specification"
                            // When an input/output or output parameter was returned, 
                            // the SQL type was an exact or approximate numeric, a 
                            // datetime, or an interval data type; the C type was 
                            // SQL_C_CHAR; and the value in the column was not a valid 
                            // literal of the bound SQL type.
                            $found = strpos($message, $errors['22018']);
                        } else {
                            // When not AE enabled, expected to fail to convert 
                            // whatever char type to integers
                            $msg = "Error converting data type $dataType to int"; 
                            $found = strpos($message, $msg);                            
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
    $tbname = "test_nchar_types";
    $spname = "test_nchar_proc";
    $conn = connect();
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
    unset($conn);
?>
--EXPECT--
Done