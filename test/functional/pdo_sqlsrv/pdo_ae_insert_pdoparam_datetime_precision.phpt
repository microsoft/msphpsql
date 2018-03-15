--TEST--
Test for inserting encrypted data into datetime types with different precisions columns
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

function compareDate($dtout, $dtin, $dataType) {
    if ($dataType == "datetimeoffset") {
        $dtarr = explode(' ', $dtin);
        if (strpos($dtout, $dtarr[0]) !== false && strpos($dtout, $dtarr[1]) !== false && strpos($dtout, $dtarr[2]) !== false) {
            return true;
        }
    } else {
        if (strpos($dtout, $dtin) !== false) {
            return true;
        }
    }
    return false;
}

$dataTypes = array("datetime2", "datetimeoffset", "time");
$precisions = array(/*0,*/ 1, 2, 4, 7);
$inputValuesInit = array("datetime2" => array("0001-01-01 00:00:00", "9999-12-31 23:59:59"),
                     "datetimeoffset" => array("0001-01-01 00:00:00 -14:00", "9999-12-31 23:59:59 +14:00"),
                     "time" => array("00:00:00", "23:59:59"));
                     
try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        foreach ($precisions as $m) {
            // add $m number of decimal digits to the some input values
            $inputValues[0] = $inputValuesInit[$dataType][0];
            $inputValues[1] = $inputValuesInit[$dataType][1];
            if ($m != 0) {
                if ($dataType == "datetime2") {
                    $inputValues[1] .= "." . str_repeat("9", $m);
                } else if ($dataType == "datetimeoffset") {
                    $dtoffsetPieces = explode(" ", $inputValues[1]);
                    $inputValues[1] = $dtoffsetPieces[0] . " " . $dtoffsetPieces[1] . "." . str_repeat("9", $m) . " " . $dtoffsetPieces[2];
                } else if ($dataType == "time") {
                    $inputValues[0] .= "." . str_repeat("0", $m);
                    $inputValues[1] .= "." . str_repeat("9", $m);
                }
            }
            $typeFull = "$dataType($m)";
            echo "\nTesting $typeFull:\n";
        
            //create table containing datetime2(m), datetimeoffset(m), or time(m) columns
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
                    if (compareDate($row['c_det'], $inputValues[0], $dataType) && compareDate($row['c_rand'], $inputValues[1], $dataType)) {
                        echo "****Conversion from $pdoParamType to $typeFull is supported****\n";
                    } else {
                        echo "Conversion from $pdoParamType to $typeFull causes data corruption\n";
                    }
                }
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
Testing datetime2(1):
****Conversion from PDO::PARAM_BOOL to datetime2(1) is supported****
****Conversion from PDO::PARAM_INT to datetime2(1) is supported****
****Conversion from PDO::PARAM_STR to datetime2(1) is supported****
****Conversion from PDO::PARAM_LOB to datetime2(1) is supported****

Testing datetime2(2):
****Conversion from PDO::PARAM_BOOL to datetime2(2) is supported****
****Conversion from PDO::PARAM_INT to datetime2(2) is supported****
****Conversion from PDO::PARAM_STR to datetime2(2) is supported****
****Conversion from PDO::PARAM_LOB to datetime2(2) is supported****

Testing datetime2(4):
****Conversion from PDO::PARAM_BOOL to datetime2(4) is supported****
****Conversion from PDO::PARAM_INT to datetime2(4) is supported****
****Conversion from PDO::PARAM_STR to datetime2(4) is supported****
****Conversion from PDO::PARAM_LOB to datetime2(4) is supported****

Testing datetime2(7):
****Conversion from PDO::PARAM_BOOL to datetime2(7) is supported****
****Conversion from PDO::PARAM_INT to datetime2(7) is supported****
****Conversion from PDO::PARAM_STR to datetime2(7) is supported****
****Conversion from PDO::PARAM_LOB to datetime2(7) is supported****

Testing datetimeoffset(1):
****Conversion from PDO::PARAM_BOOL to datetimeoffset(1) is supported****
****Conversion from PDO::PARAM_INT to datetimeoffset(1) is supported****
****Conversion from PDO::PARAM_STR to datetimeoffset(1) is supported****
****Conversion from PDO::PARAM_LOB to datetimeoffset(1) is supported****

Testing datetimeoffset(2):
****Conversion from PDO::PARAM_BOOL to datetimeoffset(2) is supported****
****Conversion from PDO::PARAM_INT to datetimeoffset(2) is supported****
****Conversion from PDO::PARAM_STR to datetimeoffset(2) is supported****
****Conversion from PDO::PARAM_LOB to datetimeoffset(2) is supported****

Testing datetimeoffset(4):
****Conversion from PDO::PARAM_BOOL to datetimeoffset(4) is supported****
****Conversion from PDO::PARAM_INT to datetimeoffset(4) is supported****
****Conversion from PDO::PARAM_STR to datetimeoffset(4) is supported****
****Conversion from PDO::PARAM_LOB to datetimeoffset(4) is supported****

Testing datetimeoffset(7):
****Conversion from PDO::PARAM_BOOL to datetimeoffset(7) is supported****
****Conversion from PDO::PARAM_INT to datetimeoffset(7) is supported****
****Conversion from PDO::PARAM_STR to datetimeoffset(7) is supported****
****Conversion from PDO::PARAM_LOB to datetimeoffset(7) is supported****

Testing time(1):
****Conversion from PDO::PARAM_BOOL to time(1) is supported****
****Conversion from PDO::PARAM_INT to time(1) is supported****
****Conversion from PDO::PARAM_STR to time(1) is supported****
****Conversion from PDO::PARAM_LOB to time(1) is supported****

Testing time(2):
****Conversion from PDO::PARAM_BOOL to time(2) is supported****
****Conversion from PDO::PARAM_INT to time(2) is supported****
****Conversion from PDO::PARAM_STR to time(2) is supported****
****Conversion from PDO::PARAM_LOB to time(2) is supported****

Testing time(4):
****Conversion from PDO::PARAM_BOOL to time(4) is supported****
****Conversion from PDO::PARAM_INT to time(4) is supported****
****Conversion from PDO::PARAM_STR to time(4) is supported****
****Conversion from PDO::PARAM_LOB to time(4) is supported****

Testing time(7):
****Conversion from PDO::PARAM_BOOL to time(7) is supported****
****Conversion from PDO::PARAM_INT to time(7) is supported****
****Conversion from PDO::PARAM_STR to time(7) is supported****
****Conversion from PDO::PARAM_LOB to time(7) is supported****