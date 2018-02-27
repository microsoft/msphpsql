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

$dataType = "float";
$bits = array(1, 12, 24, 36, 53);
$inputValues = array(9223372036854775808.9223372036854775808, -9223372036854775808.9223372036854775808);

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
    foreach ($bits as $bit) {
        $type = "$dataType($bit)";
        echo "\nTesting $type:\n";
        
        //create and populate table
        $tbname = "test_float";
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
                    echo "Fetching $type as PDO param type $pdoParamType should return NULL\n";
                }
            } else {
                echo "****PDO param type $pdoParamType is compatible with encrypted $type****\n";
                echo "c_det: $det\n";
                echo "c_rand: $rand\n";
            }
        }
        dropTable($conn, $tbname);
    }
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
Testing float(1):
****PDO param type PDO::PARAM_STR is compatible with encrypted float(1)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(1)****
c_det: 9.223372E+18
c_rand: -9.223372E+18

Testing float(12):
****PDO param type PDO::PARAM_STR is compatible with encrypted float(12)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(12)****
c_det: 9.223372E+18
c_rand: -9.223372E+18

Testing float(24):
****PDO param type PDO::PARAM_STR is compatible with encrypted float(24)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(24)****
c_det: 9.223372E+18
c_rand: -9.223372E+18

Testing float(36):
****PDO param type PDO::PARAM_STR is compatible with encrypted float(36)****
c_det: 9.2233720368548004E+18
c_rand: -9.2233720368548004E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(36)****
c_det: 9.2233720368548004E+18
c_rand: -9.2233720368548004E+18

Testing float(53):
****PDO param type PDO::PARAM_STR is compatible with encrypted float(53)****
c_det: 9.2233720368548004E+18
c_rand: -9.2233720368548004E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(53)****
c_det: 9.2233720368548004E+18
c_rand: -9.2233720368548004E+18