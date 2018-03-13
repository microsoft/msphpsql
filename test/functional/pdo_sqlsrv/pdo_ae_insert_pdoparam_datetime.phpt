--TEST--
Test for inserting encrypted data into datetime types columns
--DESCRIPTION--
Test conversions between different datetime types
With or without Always Encrypted, implicit conversion works if:
1. From input of PDO::PARAM_BOOL to a any datetime column
2. From input of PDO::PARAM_INT to a any datetime column
3. From input of PDO::PARAM_STR to a any datetime column
4. From input of PDO::PARAM_LOB to a any datetime column
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array( "date", "datetime", "smalldatetime");

try {
    $conn = connect();
    foreach ($dataTypes as $dataType) {
        echo "\nTesting $dataType:\n";

        // create table containing date, datetime or smalldatetime columns
        $tbname = "test_" . $dataType;
        $colMetaArr = array( new ColumnMeta($dataType, "c_det"), new ColumnMeta($dataType, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);

        // insert by specifying PDO::PARAM_ types
        foreach ($pdoParamTypes as $pdoParamType) {
            $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
            $r;
            $stmt = insertRow($conn, $tbname, array( "c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
            
            // check the case when inserting as PDO::PARAM_NULL
            // with or without AE: NULL is inserted
            if ($pdoParamType == "PDO::PARAM_NULL") {
                if ($r === false) {
                    echo "Conversion from $pdoParamType to $dataType should be supported\n";
                } else {
                    $sql = "SELECT c_det, c_rand FROM $tbname";
                    $stmt = $conn->query($sql);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!is_null($row['c_det']) || !is_null($row['c_rand'])) {
                        echo "Conversion from $pdoParamType to $dataType should insert NULL\n";
                    }
                }
            // check the case when inserting as PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_STR or PDO::PARAM_LOB
            // with or without AE: should work
            } else {
                $sql = "SELECT c_det, c_rand FROM $tbname";
                $stmt = $conn->query($sql);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (strpos($row['c_det'], $inputValues[0]) !== false && strpos($row['c_rand'], $inputValues[1]) !== false) {
                    echo "****Conversion from $pdoParamType to $dataType is supported****\n";
                } else {
                    echo "Conversion from $pdoParamType to $dataType causes data corruption\n";
                }
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
Testing date:
****Conversion from PDO::PARAM_BOOL to date is supported****
****Conversion from PDO::PARAM_INT to date is supported****
****Conversion from PDO::PARAM_STR to date is supported****
****Conversion from PDO::PARAM_LOB to date is supported****

Testing datetime:
****Conversion from PDO::PARAM_BOOL to datetime is supported****
****Conversion from PDO::PARAM_INT to datetime is supported****
****Conversion from PDO::PARAM_STR to datetime is supported****
****Conversion from PDO::PARAM_LOB to datetime is supported****

Testing smalldatetime:
****Conversion from PDO::PARAM_BOOL to smalldatetime is supported****
****Conversion from PDO::PARAM_INT to smalldatetime is supported****
****Conversion from PDO::PARAM_STR to smalldatetime is supported****
****Conversion from PDO::PARAM_LOB to smalldatetime is supported****