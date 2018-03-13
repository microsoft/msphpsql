--TEST--
Test for inserting encrypted data into binary types columns with different sizes
--DESCRIPTION--
Test conversions between different binary types of different sizes
With or without Always Encrypted, implicit conversion works if:
1. From input of PDO::PARAM_STR to a any binary column
2. From input of PDO::PARAM_LOB to a any binary column
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
        $maxcol = strpos($dataType, "(max)");
        foreach ($lengths as $m) {
            if ($maxcol !== false) {
                $typeFull = $dataType;
            } else {
                $typeFull = "$dataType($m)";
            }
            echo "\nTesting $typeFull:\n";
                
            // create table containing binary(m) or varbinary(m) columns
            $tbname = "test_" . str_replace(array('(', ')'), '', $dataType) . $m;
            $colMetaArr = array(new ColumnMeta($typeFull, "c_det"), new ColumnMeta($typeFull, "c_rand", null, "randomized"));
            createTable($conn, $tbname, $colMetaArr);
            $inputValues = array(str_repeat("d", $m), str_repeat("r", $m));
            
            // insert by specifying PDO::PARAM_ types
            foreach ($pdoParamTypes as $pdoParamType) {
                $r;
                if ($pdoParamType == 'PDO::PARAM_STR' || $pdoParamType == 'PDO::PARAM_LOB') {
                    $stmt = insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType, 0, "PDO::SQLSRV_ENCODING_BINARY"),
                                                            "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType, 0, "PDO::SQLSRV_ENCODING_BINARY")), "prepareBindParam", $r);
                } else {
                    $stmt = insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
                }
                
                // check the case when inserting as PDO::PARAM_BOOL or PDO::PARAM_INT
                // with or without AE: should not work
                if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_INT") {
                    if ($r !== false) {
                        echo "Conversion from $pdoParamType to $typeFull should not be supported\n";
                    }
                // check the case when inserting as PDO::PARAM_NULL
                // with AE: NULL is inserted
                // without AE: insertion fails
                } elseif ($pdoParamType == "PDO::PARAM_NULL") {
                    if (isAEConnected()) {
                        if ($r === false) {
                            echo "Conversion from $pdoParamType to $typeFull should be supported\n";
                        } else {
                            $sql = "SELECT c_det, c_rand FROM $tbname";
                            $stmt = $conn->query($sql);
                            $row = $stmt->fetch(PDO::FETCH_ASSOC);
                            if (!is_null($row['c_det']) && !is_null($row['c_rand'])) {
                                echo "Conversion from $pdoParamType to $typeFull should insert NULL\n";
                            }
                        }
                    } else {
                        if ($r !== false) {
                            echo "Conversion from $pdoParamType to $typeFull should not be supported\n";
                        }
                    }
                // check the case when inserting as PDO::PARAM_STR or PDO::PARAM_LOB
                // with or without AE: should work
                } else {
                    if ($r === false) {
                        echo "Conversion from $pdoParamType to $typeFull should be supported\n";
                    } else {
                        $sql = "SELECT c_det, c_rand FROM $tbname";
                        $stmt = $conn->query($sql);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (trim($row['c_det']) == $inputValues[0] && trim($row['c_rand']) == $inputValues[1]) {
                            echo "****Conversion from $pdoParamType to $typeFull is supported****\n";
                        } else {
                            echo "Conversion from $pdoParamType to $typeFull causes data corruption\n";
                        }
                    }
                }
                // cleanup
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
****Conversion from PDO::PARAM_STR to binary(2) is supported****
****Conversion from PDO::PARAM_LOB to binary(2) is supported****

Testing binary(8):
****Conversion from PDO::PARAM_STR to binary(8) is supported****
****Conversion from PDO::PARAM_LOB to binary(8) is supported****

Testing binary(64):
****Conversion from PDO::PARAM_STR to binary(64) is supported****
****Conversion from PDO::PARAM_LOB to binary(64) is supported****

Testing binary(512):
****Conversion from PDO::PARAM_STR to binary(512) is supported****
****Conversion from PDO::PARAM_LOB to binary(512) is supported****

Testing binary(4000):
****Conversion from PDO::PARAM_STR to binary(4000) is supported****
****Conversion from PDO::PARAM_LOB to binary(4000) is supported****

Testing varbinary(2):
****Conversion from PDO::PARAM_STR to varbinary(2) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(2) is supported****

Testing varbinary(8):
****Conversion from PDO::PARAM_STR to varbinary(8) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(8) is supported****

Testing varbinary(64):
****Conversion from PDO::PARAM_STR to varbinary(64) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(64) is supported****

Testing varbinary(512):
****Conversion from PDO::PARAM_STR to varbinary(512) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(512) is supported****

Testing varbinary(4000):
****Conversion from PDO::PARAM_STR to varbinary(4000) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(4000) is supported****

Testing varbinary(max):
****Conversion from PDO::PARAM_STR to varbinary(max) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(max) is supported****

Testing varbinary(max):
****Conversion from PDO::PARAM_STR to varbinary(max) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(max) is supported****

Testing varbinary(max):
****Conversion from PDO::PARAM_STR to varbinary(max) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(max) is supported****

Testing varbinary(max):
****Conversion from PDO::PARAM_STR to varbinary(max) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(max) is supported****

Testing varbinary(max):
****Conversion from PDO::PARAM_STR to varbinary(max) is supported****
****Conversion from PDO::PARAM_LOB to varbinary(max) is supported****