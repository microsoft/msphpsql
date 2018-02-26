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
    $conn = connect();
    foreach ($bits as $bit) {
        $type = "$dataType($bit)";
        echo "\nTesting $type:\n";
        
        //create and populate table
        $tbname = "test_float1";
        $colMetaArr = array(new ColumnMeta($type, "c_det"), new ColumnMeta($type, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);

        // test each PDO::PARAM_ type
        foreach ($pdoParamTypes as $pdoParamType) {
            // insert a row
            $r;
            $stmt = insertRow($conn, $tbname, array( "c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
            if ($r === false) {
                isIncompatibleTypesError($stmt, $type, $pdoParamType);
            } else {
                echo "****PDO param type $pdoParamType is compatible with encrypted $type****\n";
                fetchAll($conn, $tbname);
            }
            $conn->query("TRUNCATE TABLE $tbname");
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
****PDO param type PDO::PARAM_BOOL is compatible with encrypted float(1)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_NULL is compatible with encrypted float(1)****
c_det:
c_rand:
****PDO param type PDO::PARAM_INT is compatible with encrypted float(1)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_STR is compatible with encrypted float(1)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(1)****
c_det: 9.223372E+18
c_rand: -9.223372E+18

Testing float(12):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted float(12)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_NULL is compatible with encrypted float(12)****
c_det:
c_rand:
****PDO param type PDO::PARAM_INT is compatible with encrypted float(12)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_STR is compatible with encrypted float(12)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(12)****
c_det: 9.223372E+18
c_rand: -9.223372E+18

Testing float(24):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted float(24)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_NULL is compatible with encrypted float(24)****
c_det:
c_rand:
****PDO param type PDO::PARAM_INT is compatible with encrypted float(24)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_STR is compatible with encrypted float(24)****
c_det: 9.223372E+18
c_rand: -9.223372E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(24)****
c_det: 9.223372E+18
c_rand: -9.223372E+18

Testing float(36):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted float(36)****
c_det: 9.2233720368547758E+18
c_rand: -9.2233720368547758E+18
****PDO param type PDO::PARAM_NULL is compatible with encrypted float(36)****
c_det:
c_rand:
****PDO param type PDO::PARAM_INT is compatible with encrypted float(36)****
c_det: 9.2233720368547758E+18
c_rand: -9.2233720368547758E+18
****PDO param type PDO::PARAM_STR is compatible with encrypted float(36)****
c_det: 9.2233720368548004E+18
c_rand: -9.2233720368548004E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(36)****
c_det: 9.2233720368547758E+18
c_rand: -9.2233720368547758E+18

Testing float(53):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted float(53)****
c_det: 9.2233720368547758E+18
c_rand: -9.2233720368547758E+18
****PDO param type PDO::PARAM_NULL is compatible with encrypted float(53)****
c_det:
c_rand:
****PDO param type PDO::PARAM_INT is compatible with encrypted float(53)****
c_det: 9.2233720368547758E+18
c_rand: -9.2233720368547758E+18
****PDO param type PDO::PARAM_STR is compatible with encrypted float(53)****
c_det: 9.2233720368548004E+18
c_rand: -9.2233720368548004E+18
****PDO param type PDO::PARAM_LOB is compatible with encrypted float(53)****
c_det: 9.2233720368547758E+18
c_rand: -9.2233720368547758E+18