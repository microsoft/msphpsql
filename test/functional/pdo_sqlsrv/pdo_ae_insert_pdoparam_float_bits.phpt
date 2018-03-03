--TEST--
Test for inserting encrypted data into float types columns
--DESCRIPTION--
Test conversions between different float types
With or without Always Encrypted, implicit conversion works if:
1. From input of PDO::PARAM_BOOL to a float column
2. From input of PDO::PARAM_INT to a float column
3. From input of PDO::PARAM_STR to a float column
4. From input of PDO::PARAM_LOB to a float column
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataType = "float";
$bits = array(1, 12, 24, 36, 53);
$inputValues = array(9223372036854775808.9223372036854775808, -9223372036854775808.9223372036854775808);
$numint = 19;

try {
    $conn = connect();
    foreach ($bits as $m) {
        // compute the epsilon for comparing doubles
        // when $m <= 24, the precision is 7 digits
        // when $m > 24, the precision is 15 digits, but PHP float only supports up to 14 digits
        $epsilon;
        if ($m <= 24) {
            $epsilon = pow(10, $numint - 7);
        } else {
            $epsilon = pow(10, $numint - 14);
        }
        
        $typeFull = "$dataType($m)";
        echo "\nTesting $typeFull:\n";
        
        //create table containing float(m) columns
        $tbname = "test_" . $dataType . $m;
        $colMetaArr = array(new ColumnMeta($typeFull, "c_det"), new ColumnMeta($typeFull, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);

        // insert by specifying PDO::PARAM_ types
        foreach ($pdoParamTypes as $pdoParamType) {
            $r;
            $stmt = insertRow($conn, $tbname, array( "c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
            
            // check the case when inserting as PDO::PARAM_NULL
            // with or without AE: NULL is inserted
            if ($pdoParamType == "PDO::PARAM_NULL") {
                if ($r === false) {
                    echo "Conversion from $pdoParamType to $typeFull should be supported\n";
                } else {
                    $sql = "SELECT c_det, c_rand FROM $tbname";
                    $stmt = $conn->query($sql);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!is_null($row['c_det']) || !is_null($row['c_rand'])) {
                        echo "Conversion from $pdoParamType to $typeFull should insert NULL\n";
                    }
                }
            // check the case when inserting as PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_STR or PDO::PARAM_LOB
            // with or without AE: should work
            } else {
                $sql = "SELECT c_det, c_rand FROM $tbname";
                $stmt = $conn->query($sql);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if (abs($row['c_det'] - $inputValues[0]) < $epsilon && abs($row['c_rand'] - $inputValues[1]) < $epsilon) {
                    echo "****Conversion from $pdoParamType to $typeFull is supported****\n";
                } else {
                    echo "Conversion from $pdoParamType to $typeFull causes data corruption\n";
                }
            }
            // cleanup
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
****Conversion from PDO::PARAM_BOOL to float(1) is supported****
****Conversion from PDO::PARAM_INT to float(1) is supported****
****Conversion from PDO::PARAM_STR to float(1) is supported****
****Conversion from PDO::PARAM_LOB to float(1) is supported****

Testing float(12):
****Conversion from PDO::PARAM_BOOL to float(12) is supported****
****Conversion from PDO::PARAM_INT to float(12) is supported****
****Conversion from PDO::PARAM_STR to float(12) is supported****
****Conversion from PDO::PARAM_LOB to float(12) is supported****

Testing float(24):
****Conversion from PDO::PARAM_BOOL to float(24) is supported****
****Conversion from PDO::PARAM_INT to float(24) is supported****
****Conversion from PDO::PARAM_STR to float(24) is supported****
****Conversion from PDO::PARAM_LOB to float(24) is supported****

Testing float(36):
****Conversion from PDO::PARAM_BOOL to float(36) is supported****
****Conversion from PDO::PARAM_INT to float(36) is supported****
****Conversion from PDO::PARAM_STR to float(36) is supported****
****Conversion from PDO::PARAM_LOB to float(36) is supported****

Testing float(53):
****Conversion from PDO::PARAM_BOOL to float(53) is supported****
****Conversion from PDO::PARAM_INT to float(53) is supported****
****Conversion from PDO::PARAM_STR to float(53) is supported****
****Conversion from PDO::PARAM_LOB to float(53) is supported****