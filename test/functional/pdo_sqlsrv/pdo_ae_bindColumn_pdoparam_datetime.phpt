--TEST--
Test for inserting and retrieving encrypted data of datetime types
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array("date", "datetime", "smalldatetime");
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
            
            if ($pdoParamType == "PDO::PARAM_BOOL" || $pdoParamType == "PDO::PARAM_NULL" || $pdoParamType == "PDO::PARAM_INT") {
                if (!is_null($det) || !is_null($rand)) {
                    echo "Retrieving encrypted $type data as $pdoParamType should not work\n";
                }
            } else {
                echo "****PDO param type $pdoParamType is compatible with encrypted $dataType****\n";
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
Testing date:
****PDO param type PDO::PARAM_STR is compatible with encrypted date****
c_det: 0001-01-01
c_rand: 9999-12-31
****PDO param type PDO::PARAM_LOB is compatible with encrypted date****
c_det: 0001-01-01
c_rand: 9999-12-31

Testing datetime:
****PDO param type PDO::PARAM_STR is compatible with encrypted datetime****
c_det: 1753-01-01 00:00:00.000
c_rand: 9999-12-31 23:59:59.997
****PDO param type PDO::PARAM_LOB is compatible with encrypted datetime****
c_det: 1753-01-01 00:00:00.000
c_rand: 9999-12-31 23:59:59.997

Testing smalldatetime:
****PDO param type PDO::PARAM_STR is compatible with encrypted smalldatetime****
c_det: 1900-01-01 00:00:00
c_rand: 2079-06-05 23:59:00
****PDO param type PDO::PARAM_LOB is compatible with encrypted smalldatetime****
c_det: 1900-01-01 00:00:00
c_rand: 2079-06-05 23:59:00