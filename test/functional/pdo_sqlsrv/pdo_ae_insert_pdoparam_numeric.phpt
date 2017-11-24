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
$dataTypes = array( "bit", "tinyint", "smallint", "int", "decimal(18,5)", "numeric(10,5)", "float", "real" );
try {
    $conn = connect();
    foreach ($dataTypes as $dataType) {
        echo "\nTesting $dataType:\n";

        // create table
        $tbname = getTableName();
        $colMetaArr = array(new ColumnMeta($dataType, "c_det"), new ColumnMeta($dataType, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);

        // test each PDO::PARAM_ type
        foreach ($pdoParamTypes as $pdoParamType) {
            // insert a row
            $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
            $r;
            if ($dataType == "decimal(18,5)") {
                $stmt = insertRow($conn, $tbname, array( "c_det" => new BindParamOp(1, (string)$inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, (string)$inputValues[1], $pdoParamType)), "prepareBindParam", $r);
            } else {
                $stmt = insertRow($conn, $tbname, array( "c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
            }
            if ($r === false) {
                isIncompatibleTypesError($stmt, $dataType, $pdoParamType);
            } else {
                echo "****PDO param type $pdoParamType is compatible with encrypted $dataType****\n";
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

Testing bit:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted bit****
c_det: 1
c_rand: 0
****PDO param type PDO::PARAM_NULL is compatible with encrypted bit****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted bit****
c_det: 1
c_rand: 0
****PDO param type PDO::PARAM_STR is compatible with encrypted bit****
c_det: 1
c_rand: 0
****PDO param type PDO::PARAM_LOB is compatible with encrypted bit****
c_det: 1
c_rand: 0

Testing tinyint:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted tinyint****
c_det: 0
c_rand: 1
****PDO param type PDO::PARAM_NULL is compatible with encrypted tinyint****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted tinyint****
c_det: 0
c_rand: 255
****PDO param type PDO::PARAM_STR is compatible with encrypted tinyint****
c_det: 0
c_rand: 255
****PDO param type PDO::PARAM_LOB is compatible with encrypted tinyint****
c_det: 0
c_rand: 255

Testing smallint:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted smallint****
c_det: 1
c_rand: 1
****PDO param type PDO::PARAM_NULL is compatible with encrypted smallint****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted smallint****
c_det: -32767
c_rand: 32767
****PDO param type PDO::PARAM_STR is compatible with encrypted smallint****
c_det: -32767
c_rand: 32767
****PDO param type PDO::PARAM_LOB is compatible with encrypted smallint****
c_det: -32767
c_rand: 32767

Testing int:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted int****
c_det: 1
c_rand: 1
****PDO param type PDO::PARAM_NULL is compatible with encrypted int****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted int****
c_det: -2147483647
c_rand: 2147483647
****PDO param type PDO::PARAM_STR is compatible with encrypted int****
c_det: -2147483647
c_rand: 2147483647
****PDO param type PDO::PARAM_LOB is compatible with encrypted int****
c_det: -2147483647
c_rand: 2147483647

Testing decimal(18,5):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted decimal(18,5)****
c_det: -9223372036854.80000
c_rand: 9223372036854.80000
****PDO param type PDO::PARAM_NULL is compatible with encrypted decimal(18,5)****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted decimal(18,5)****
c_det: -9223372036854.80000
c_rand: 9223372036854.80000
****PDO param type PDO::PARAM_STR is compatible with encrypted decimal(18,5)****
c_det: -9223372036854.80000
c_rand: 9223372036854.80000
****PDO param type PDO::PARAM_LOB is compatible with encrypted decimal(18,5)****
c_det: -9223372036854.80000
c_rand: 9223372036854.80000

Testing numeric(10,5):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted numeric(10,5)****
c_det: -21474.83647
c_rand: 21474.83647
****PDO param type PDO::PARAM_NULL is compatible with encrypted numeric(10,5)****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted numeric(10,5)****
c_det: -21474.83647
c_rand: 21474.83647
****PDO param type PDO::PARAM_STR is compatible with encrypted numeric(10,5)****
c_det: -21474.83647
c_rand: 21474.83647
****PDO param type PDO::PARAM_LOB is compatible with encrypted numeric(10,5)****
c_det: -21474.83647
c_rand: 21474.83647

Testing float:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted float****
c_det: -9223372036.8547993
c_rand: 9223372036.8547993
****PDO param type PDO::PARAM_NULL is compatible with encrypted float****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted float****
c_det: -9223372036.8547993
c_rand: 9223372036.8547993
****PDO param type PDO::PARAM_STR is compatible with encrypted float****
c_det: -9223372036.8547993
c_rand: 9223372036.8547993
****PDO param type PDO::PARAM_LOB is compatible with encrypted float****
c_det: -9223372036.8547993
c_rand: 9223372036.8547993

Testing real:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted real****
c_det: -2147.4829
c_rand: 2147.4829
****PDO param type PDO::PARAM_NULL is compatible with encrypted real****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted real****
c_det: -2147.4829
c_rand: 2147.4829
****PDO param type PDO::PARAM_STR is compatible with encrypted real****
c_det: -2147.4829
c_rand: 2147.4829
****PDO param type PDO::PARAM_LOB is compatible with encrypted real****
c_det: -2147.4829
c_rand: 2147.4829
