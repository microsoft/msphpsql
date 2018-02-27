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

$dataTypes = array( "bit", "tinyint", "smallint", "int", "bigint", "real");
try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        echo "\nTesting $dataType:\n";

        // create and populate table
        $tbname = getTableName();
        $colMetaArr = array(new ColumnMeta($dataType, "c_det"), new ColumnMeta($dataType, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);
        $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
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
            if ($row != false) {
                if (is_null($det) || is_null($rand)) {
                    echo "PDO param type $pdoParamType is not compatible with encrypted $dataType\n";
                } else {
                    echo "****PDO param type $pdoParamType is compatible with encrypted $dataType****\n";
                    echo "c_det: $det\n";
                    echo "c_rand: $rand\n";
                }
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
Testing bit:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted bit****
c_det: 1
c_rand: 0
PDO param type PDO::PARAM_NULL is not compatible with encrypted bit
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
c_rand: 255
PDO param type PDO::PARAM_NULL is not compatible with encrypted tinyint
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
c_det: -32767
c_rand: 32767
PDO param type PDO::PARAM_NULL is not compatible with encrypted smallint
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
c_det: -2147483647
c_rand: 2147483647
PDO param type PDO::PARAM_NULL is not compatible with encrypted int
****PDO param type PDO::PARAM_INT is compatible with encrypted int****
c_det: -2147483647
c_rand: 2147483647
****PDO param type PDO::PARAM_STR is compatible with encrypted int****
c_det: -2147483647
c_rand: 2147483647
****PDO param type PDO::PARAM_LOB is compatible with encrypted int****
c_det: -2147483647
c_rand: 2147483647

Testing bigint:
PDO param type PDO::PARAM_BOOL is not compatible with encrypted bigint
PDO param type PDO::PARAM_NULL is not compatible with encrypted bigint
PDO param type PDO::PARAM_INT is not compatible with encrypted bigint
****PDO param type PDO::PARAM_STR is compatible with encrypted bigint****
c_det: -922337203685479936
c_rand: 922337203685479936
****PDO param type PDO::PARAM_LOB is compatible with encrypted bigint****
c_det: -922337203685479936
c_rand: 922337203685479936

Testing real:
****PDO param type PDO::PARAM_BOOL is compatible with encrypted real****
c_det: -2147
c_rand: 2147
PDO param type PDO::PARAM_NULL is not compatible with encrypted real
****PDO param type PDO::PARAM_INT is compatible with encrypted real****
c_det: -2147
c_rand: 2147
****PDO param type PDO::PARAM_STR is compatible with encrypted real****
c_det: -2147.4829
c_rand: 2147.4829
****PDO param type PDO::PARAM_LOB is compatible with encrypted real****
c_det: -2147.4829
c_rand: 2147.4829