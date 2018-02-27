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

$dataTypes = array("datetime2", "datetimeoffset", "time");
$precisions = array(/*0,*/ 1, 2, 4, 7);
$inputValuesInit = array("datetime2" => array("0001-01-01 00:00:00", "9999-12-31 23:59:59"),
                     "datetimeoffset" => array("0001-01-01 00:00:00 -14:00", "9999-12-31 23:59:59 +14:00"),
                     "time" => array("00:00:00", "23:59:59"));

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
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
            echo "\nTesting $type:\n";
        
            //create and populate table
            $tbname = "test_datetime";
            $colMetaArr = array(new ColumnMeta($type, "c_det"), new ColumnMeta($type, "c_rand", null, "randomized"));
            createTable($conn, $tbname, $colMetaArr);
            insertRow($conn, $tbname, array("c_det" => $inputValues[0], "c_rand" => $inputValues[1]));
                
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
                    echo "****PDO param type $pdoParamType is compatible with encrypted $type****\n";
                    echo "c_det: $det\n";
                    echo "c_rand: $rand\n";
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
Testing datetime2(1):
****PDO param type PDO::PARAM_STR is compatible with encrypted datetime2(1)****
c_det: 0001-01-01 00:00:00.0
c_rand: 9999-12-31 23:59:59.9
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetime2(1)****
c_det: 0001-01-01 00:00:00.0
c_rand: 9999-12-31 23:59:59.9

Testing datetime2(2):
****PDO param type PDO::PARAM_STR is compatible with encrypted datetime2(2)****
c_det: 0001-01-01 00:00:00.00
c_rand: 9999-12-31 23:59:59.99
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetime2(2)****
c_det: 0001-01-01 00:00:00.00
c_rand: 9999-12-31 23:59:59.99

Testing datetime2(4):
****PDO param type PDO::PARAM_STR is compatible with encrypted datetime2(4)****
c_det: 0001-01-01 00:00:00.0000
c_rand: 9999-12-31 23:59:59.9999
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetime2(4)****
c_det: 0001-01-01 00:00:00.0000
c_rand: 9999-12-31 23:59:59.9999

Testing datetime2(7):
****PDO param type PDO::PARAM_STR is compatible with encrypted datetime2(7)****
c_det: 0001-01-01 00:00:00.0000000
c_rand: 9999-12-31 23:59:59.9999999
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetime2(7)****
c_det: 0001-01-01 00:00:00.0000000
c_rand: 9999-12-31 23:59:59.9999999

Testing datetimeoffset(1):
****PDO param type PDO::PARAM_STR is compatible with encrypted datetimeoffset(1)****
c_det: 0001-01-01 00:00:00.0 -14:00
c_rand: 9999-12-31 23:59:59.9 +14:00
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetimeoffset(1)****
c_det: 0001-01-01 00:00:00.0 -14:00
c_rand: 9999-12-31 23:59:59.9 +14:00

Testing datetimeoffset(2):
****PDO param type PDO::PARAM_STR is compatible with encrypted datetimeoffset(2)****
c_det: 0001-01-01 00:00:00.00 -14:00
c_rand: 9999-12-31 23:59:59.99 +14:00
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetimeoffset(2)****
c_det: 0001-01-01 00:00:00.00 -14:00
c_rand: 9999-12-31 23:59:59.99 +14:00

Testing datetimeoffset(4):
****PDO param type PDO::PARAM_STR is compatible with encrypted datetimeoffset(4)****
c_det: 0001-01-01 00:00:00.0000 -14:00
c_rand: 9999-12-31 23:59:59.9999 +14:00
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetimeoffset(4)****
c_det: 0001-01-01 00:00:00.0000 -14:00
c_rand: 9999-12-31 23:59:59.9999 +14:00

Testing datetimeoffset(7):
****PDO param type PDO::PARAM_STR is compatible with encrypted datetimeoffset(7)****
c_det: 0001-01-01 00:00:00.0000000 -14:00
c_rand: 9999-12-31 23:59:59.9999999 +14:00
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetimeoffset(7)****
c_det: 0001-01-01 00:00:00.0000000 -14:00
c_rand: 9999-12-31 23:59:59.9999999 +14:00

Testing time(1):
****PDO param type PDO::PARAM_STR is compatible with encrypted time(1)****
c_det: 00:00:00.0
c_rand: 23:59:59.9
****PDO param type PDO::PARAM_LOB is compatible with encrypted time(1)****
c_det: 00:00:00.0
c_rand: 23:59:59.9

Testing time(2):
****PDO param type PDO::PARAM_STR is compatible with encrypted time(2)****
c_det: 00:00:00.00
c_rand: 23:59:59.99
****PDO param type PDO::PARAM_LOB is compatible with encrypted time(2)****
c_det: 00:00:00.00
c_rand: 23:59:59.99

Testing time(4):
****PDO param type PDO::PARAM_STR is compatible with encrypted time(4)****
c_det: 00:00:00.0000
c_rand: 23:59:59.9999
****PDO param type PDO::PARAM_LOB is compatible with encrypted time(4)****
c_det: 00:00:00.0000
c_rand: 23:59:59.9999

Testing time(7):
****PDO param type PDO::PARAM_STR is compatible with encrypted time(7)****
c_det: 00:00:00.0000000
c_rand: 23:59:59.9999999
****PDO param type PDO::PARAM_LOB is compatible with encrypted time(7)****
c_det: 00:00:00.0000000
c_rand: 23:59:59.9999999