--TEST--
Test for inserting encrypted data into nchar types columns with different sizes
--DESCRIPTION--
Test conversions between different nchar types of different sizes
With or without Always Encrypted, implicit conversion works if:
1. From input of PDO::PARAM_BOOL to any nchar column
2. From input of PDO::PARAM_INT to any nchar column
3. From input of PDO::PARAM_STR to any nchar column
4. From input of PDO::PARAM_LOB to any nchar column
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array("nchar", "nvarchar", "nvarchar(max)");
$lengths = array(1, 8, 64, 512, 4000);

try {
    $conn = connect();
    foreach ($dataTypes as $dataType) {
        $maxcol = strpos($dataType, "(max)");
        foreach ($lengths as $m) {
            if ($maxcol !== false) {
                $typeFull = $dataType;
            } else {
                $typeFull = "$dataType($m)";
            }
            echo "\nTesting $typeFull:\n";
                
            // create table containing nchar(m) or nvarchar(m) columns
            // only one column is created because a row has a limitation of 8060 bytes
            // for lengths 4096 and 8000, cannot create 2 columns as it will exceed the maximum row sizes
            // for AE, only testing deterministic here, randomized is tested in the char test
            $tbname = "test_" . str_replace(array('(', ')'), '', $dataType) . $m;
            $colMetaArr = array(new ColumnMeta($typeFull, "c1"));
            createTable($conn, $tbname, $colMetaArr);
            $input = str_repeat("d", $m);
                
            // insert by specifying PDO::PARAM_ types
            foreach ($pdoParamTypes as $pdoParamType) {
                $r;
                $stmt = insertRow($conn, $tbname, array( "c1" => new BindParamOp(1, $input, $pdoParamType)), "prepareBindParam", $r);
                
                // check the case when inserting as PDO::PARAM_NULL
                // with or without AE: NULL is inserted
                if ($pdoParamType == "PDO::PARAM_NULL") {
                    if ($r === false) {
                        echo "Conversion from $pdoParamType to $typeFull should be supported\n";
                    } else {
                        $sql = "SELECT c1 FROM $tbname";
                        $stmt = $conn->query($sql);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if (!is_null($row['c1'])) {
                            echo "Conversion from $pdoParamType to $typeFull should insert NULL\n";
                        }
                    }
                // check the case when inserting as PDO::PARAM_BOOL, PDO::PARAM_INT, PDO::PARAM_STR or PDO{{PARAM_LOB
                // with or without AE: should work
                } else {
                    $sql = "SELECT c1 FROM $tbname";
                    $stmt = $conn->query($sql);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (strlen($row['c1']) == $m) {
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
    }
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
Testing nchar(1):
****Conversion from PDO::PARAM_BOOL to nchar(1) is supported****
****Conversion from PDO::PARAM_INT to nchar(1) is supported****
****Conversion from PDO::PARAM_STR to nchar(1) is supported****
****Conversion from PDO::PARAM_LOB to nchar(1) is supported****

Testing nchar(8):
****Conversion from PDO::PARAM_BOOL to nchar(8) is supported****
****Conversion from PDO::PARAM_INT to nchar(8) is supported****
****Conversion from PDO::PARAM_STR to nchar(8) is supported****
****Conversion from PDO::PARAM_LOB to nchar(8) is supported****

Testing nchar(64):
****Conversion from PDO::PARAM_BOOL to nchar(64) is supported****
****Conversion from PDO::PARAM_INT to nchar(64) is supported****
****Conversion from PDO::PARAM_STR to nchar(64) is supported****
****Conversion from PDO::PARAM_LOB to nchar(64) is supported****

Testing nchar(512):
****Conversion from PDO::PARAM_BOOL to nchar(512) is supported****
****Conversion from PDO::PARAM_INT to nchar(512) is supported****
****Conversion from PDO::PARAM_STR to nchar(512) is supported****
****Conversion from PDO::PARAM_LOB to nchar(512) is supported****

Testing nchar(4000):
****Conversion from PDO::PARAM_BOOL to nchar(4000) is supported****
****Conversion from PDO::PARAM_INT to nchar(4000) is supported****
****Conversion from PDO::PARAM_STR to nchar(4000) is supported****
****Conversion from PDO::PARAM_LOB to nchar(4000) is supported****

Testing nvarchar(1):
****Conversion from PDO::PARAM_BOOL to nvarchar(1) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(1) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(1) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(1) is supported****

Testing nvarchar(8):
****Conversion from PDO::PARAM_BOOL to nvarchar(8) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(8) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(8) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(8) is supported****

Testing nvarchar(64):
****Conversion from PDO::PARAM_BOOL to nvarchar(64) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(64) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(64) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(64) is supported****

Testing nvarchar(512):
****Conversion from PDO::PARAM_BOOL to nvarchar(512) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(512) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(512) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(512) is supported****

Testing nvarchar(4000):
****Conversion from PDO::PARAM_BOOL to nvarchar(4000) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(4000) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(4000) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(4000) is supported****

Testing nvarchar(max):
****Conversion from PDO::PARAM_BOOL to nvarchar(max) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(max) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(max) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(max) is supported****

Testing nvarchar(max):
****Conversion from PDO::PARAM_BOOL to nvarchar(max) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(max) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(max) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(max) is supported****

Testing nvarchar(max):
****Conversion from PDO::PARAM_BOOL to nvarchar(max) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(max) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(max) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(max) is supported****

Testing nvarchar(max):
****Conversion from PDO::PARAM_BOOL to nvarchar(max) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(max) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(max) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(max) is supported****

Testing nvarchar(max):
****Conversion from PDO::PARAM_BOOL to nvarchar(max) is supported****
****Conversion from PDO::PARAM_INT to nvarchar(max) is supported****
****Conversion from PDO::PARAM_STR to nvarchar(max) is supported****
****Conversion from PDO::PARAM_LOB to nvarchar(max) is supported****