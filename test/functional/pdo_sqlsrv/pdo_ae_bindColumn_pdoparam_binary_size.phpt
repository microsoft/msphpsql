--TEST--
Test for inserting and retrieving encrypted data of numeric types
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array("binary", "varbinary", "varbinary(max)");
$lengths = array(1, 8, 64, 512, 4000);

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
            insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $input0, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY"),
                                            "c_rand" => new BindParamOp(2, $input1, "PDO::PARAM_LOB", 0, "PDO::SQLSRV_ENCODING_BINARY")), "prepareBindParam");
                
            // fetch with PDO::bindColumn and PDO::FETCH_BOUND
            $query = "SELECT c_det, c_rand FROM $tbname";
            foreach ($pdoParamTypes as $pdoParamType) {
                $det = "";
                $rand = "";
                $stmt = $conn->prepare($query);
                $stmt->execute();
                $stmt->bindColumn('c_det', $det, constant($pdoParamType));
                $stmt->bindColumn('c_rand', $rand, constant($pdoParamType));
                $row = $stmt->fetch(PDO::FETCH_BOUND);
                
                if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_NULL" || $pdoParamType == "PDO::PARAM_INT") {
                    if (!is_null($det) || !is_null($rand)) {
                        echo "Retrieving encrypted $type data as $pdoParamType should not work\n";
                    }
                } else {
                    if (strlen($det) == $length && strlen($rand) == $length) {
                        echo "****PDO param type $pdoParamType is compatible with encrypted $type****\n";
                    } else {
                        echo "Data corruption when fetching encrypted $type as PDO param type $pdoParamType\n";
                        print_r($stmt->errorInfo());
                    }
                }
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
Testing binary(1):
****PDO param type PDO::PARAM_STR is compatible with encrypted binary(1)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted binary(1)****

Testing binary(8):
****PDO param type PDO::PARAM_STR is compatible with encrypted binary(8)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted binary(8)****

Testing binary(64):
****PDO param type PDO::PARAM_STR is compatible with encrypted binary(64)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted binary(64)****

Testing binary(512):
****PDO param type PDO::PARAM_STR is compatible with encrypted binary(512)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted binary(512)****

Testing binary(4000):
****PDO param type PDO::PARAM_STR is compatible with encrypted binary(4000)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted binary(4000)****

Testing varbinary(1):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(1)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(1)****

Testing varbinary(8):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(8)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(8)****

Testing varbinary(64):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(64)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(64)****

Testing varbinary(512):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(512)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(512)****

Testing varbinary(4000):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(4000)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(4000)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(max)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(max)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(max)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(max)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(max)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(max)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(max)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(max)****

Testing varbinary(max):
****PDO param type PDO::PARAM_STR is compatible with encrypted varbinary(max)****
****PDO param type PDO::PARAM_LOB is compatible with encrypted varbinary(max)****