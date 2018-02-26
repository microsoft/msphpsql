--TEST--
Test for inserting and retrieving encrypted data of string types
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array("binary", "varbinary", "varbinary(max)");
$lengths = array(2, 8, 64, 512, 4000);

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        $maxtype = strpos($dataType, "(max)");
        foreach ($lengths as $length) {
            if ($maxtype !== false) {
                $type = $dataType;
            } else {
                $type = "$dataType($length)";
            }
            echo "\nTesting $type:\n";
                
            //create and populate table
            $tbname = "test_binary";
            $colMetaArr = array(new ColumnMeta($type, "c_det"), new ColumnMeta($type, "c_rand", null, "randomized"));
            createTable($conn, $tbname, $colMetaArr);
            $input0 = str_repeat("d", $length);
            $input1 = str_repeat("r", $length);
            
            // prepare statement for inserting into table
            foreach ($pdoParamTypes as $pdoParamType) {
                // insert a row
                $r;
                if ($pdoParamType == 'PDO::PARAM_STR' || $pdoParamType == 'PDO::PARAM_LOB') {
                    $stmt = insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $input0, $pdoParamType, 0, "PDO::SQLSRV_ENCODING_BINARY"),
                                                            "c_rand" => new BindParamOp(2, $input1, $pdoParamType, 0, "PDO::SQLSRV_ENCODING_BINARY")), "prepareBindParam", $r);
                } else {
                    $stmt = insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $input0, $pdoParamType), "c_rand" => new BindParamOp(2, $input1, $pdoParamType)), "prepareBindParam", $r);
                }
                
                if ($pdoParamType == "PDO::PARAM_STR" || $pdoParamType == "PDO::PARAM_LOB") {
                    if ($r === false) {
                        echo "$pdoParamType(PDO::SQLSRV_ENCODING_BINARY) should be compatible with encrypted $type\n";
                    } else {
                        $sql = "SELECT c_det, c_rand FROM $tbname";
                        $stmt = $conn->query($sql);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (strlen($row['c_det']) == $length && strlen($row['c_rand']) == $length) {
                            echo "****PDO param type $pdoParamType(PDO::SQLSRV_ENCODING_BINARY) is compatible with $type****\n";
                        } else {
                            echo "PDO param type $pdoParamType is incompatible with $type\n";
                        }
                    }
                } elseif (!isAEConnected()) {
                    if ($r !== false) {
                        echo "PDO param type $pdoParamType should not be compatible with $type\n";
                    }
                } else {
                    if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT") {
                        if ($r !== false) {
                            echo "PDO param type $pdoParamType should not be compatible with $type\n";
                            $sql = "SELECT c_det, c_rand FROM $tbname";
                            $stmt = $conn->query($sql);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            //var_dump($row);
                        } else {
                            $sql = "SELECT c_det, c_rand FROM $tbname";
                            $stmt = $conn->query($sql);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (!is_null($row['c_det']) && !is_null($row['c_rand'])) {
                                "Data inserted with $pdoParamType should be null\n";
                            }
                        }
                    }
                }
                $conn->query("TRUNCATE TABLE $tbname");
            }
            dropTable($conn, $tbname);
        }
    }
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
Testing binary(2):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(2)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(2)****

Testing binary(8):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(8)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(8)****

Testing binary(64):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(64)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(64)****

Testing binary(512):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(512)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(512)****

Testing binary(4000):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(4000)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with binary(4000)****

Testing varbinary(2):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(2)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(2)****

Testing varbinary(8):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(8)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(8)****

Testing varbinary(64):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(64)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(64)****

Testing varbinary(512):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(512)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(512)****

Testing varbinary(4000):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(4000)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(4000)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****
****PDO param type PDO::PARAM_LOB(PDO::SQLSRV_ENCODING_BINARY) is compatible with varbinary(max)****