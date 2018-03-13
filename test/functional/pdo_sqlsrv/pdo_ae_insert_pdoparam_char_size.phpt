--TEST--
Test for inserting encrypted data into char types columns with different sizes
--DESCRIPTION--
Test conversions between different char types of different sizes
With or without Always Encrypted, implicit conversion works if:
1. From input of PDO::PARAM_BOOL to any char column
2. From input of PDO::PARAM_INT to any char column
3. From input of PDO::PARAM_STR to any char column
4. From input of PDO::PARAM_LOB to any char column
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array("char", "varchar", "varchar(max)");
$lengths = array(1, 8, 64, 512, 4096, 8000);

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
                
            // create table containing a char(m) or varchar(m) column
            // only one column is created because a row has a limitation of 8060 bytes
            // for lengths 4096 and 8000, cannot create 2 columns as it will exceed the maximum row sizes
            // for AE, only testing randomized here, deterministic is tested in the nchar test
            $tbname = getTableName("test_" . str_replace(array('(', ')'), '', $dataType) . $m);
            $colMetaArr = array(new ColumnMeta($typeFull, "c1", null, "randomized"));
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
Testing char(1):
****Conversion from PDO::PARAM_BOOL to char(1) is supported****
****Conversion from PDO::PARAM_INT to char(1) is supported****
****Conversion from PDO::PARAM_STR to char(1) is supported****
****Conversion from PDO::PARAM_LOB to char(1) is supported****

Testing char(8):
****Conversion from PDO::PARAM_BOOL to char(8) is supported****
****Conversion from PDO::PARAM_INT to char(8) is supported****
****Conversion from PDO::PARAM_STR to char(8) is supported****
****Conversion from PDO::PARAM_LOB to char(8) is supported****

Testing char(64):
****Conversion from PDO::PARAM_BOOL to char(64) is supported****
****Conversion from PDO::PARAM_INT to char(64) is supported****
****Conversion from PDO::PARAM_STR to char(64) is supported****
****Conversion from PDO::PARAM_LOB to char(64) is supported****

Testing char(512):
****Conversion from PDO::PARAM_BOOL to char(512) is supported****
****Conversion from PDO::PARAM_INT to char(512) is supported****
****Conversion from PDO::PARAM_STR to char(512) is supported****
****Conversion from PDO::PARAM_LOB to char(512) is supported****

Testing char(4096):
****Conversion from PDO::PARAM_BOOL to char(4096) is supported****
****Conversion from PDO::PARAM_INT to char(4096) is supported****
****Conversion from PDO::PARAM_STR to char(4096) is supported****
****Conversion from PDO::PARAM_LOB to char(4096) is supported****

Testing char(8000):
****Conversion from PDO::PARAM_BOOL to char(8000) is supported****
****Conversion from PDO::PARAM_INT to char(8000) is supported****
****Conversion from PDO::PARAM_STR to char(8000) is supported****
****Conversion from PDO::PARAM_LOB to char(8000) is supported****

Testing varchar(1):
****Conversion from PDO::PARAM_BOOL to varchar(1) is supported****
****Conversion from PDO::PARAM_INT to varchar(1) is supported****
****Conversion from PDO::PARAM_STR to varchar(1) is supported****
****Conversion from PDO::PARAM_LOB to varchar(1) is supported****

Testing varchar(8):
****Conversion from PDO::PARAM_BOOL to varchar(8) is supported****
****Conversion from PDO::PARAM_INT to varchar(8) is supported****
****Conversion from PDO::PARAM_STR to varchar(8) is supported****
****Conversion from PDO::PARAM_LOB to varchar(8) is supported****

Testing varchar(64):
****Conversion from PDO::PARAM_BOOL to varchar(64) is supported****
****Conversion from PDO::PARAM_INT to varchar(64) is supported****
****Conversion from PDO::PARAM_STR to varchar(64) is supported****
****Conversion from PDO::PARAM_LOB to varchar(64) is supported****

Testing varchar(512):
****Conversion from PDO::PARAM_BOOL to varchar(512) is supported****
****Conversion from PDO::PARAM_INT to varchar(512) is supported****
****Conversion from PDO::PARAM_STR to varchar(512) is supported****
****Conversion from PDO::PARAM_LOB to varchar(512) is supported****

Testing varchar(4096):
****Conversion from PDO::PARAM_BOOL to varchar(4096) is supported****
****Conversion from PDO::PARAM_INT to varchar(4096) is supported****
****Conversion from PDO::PARAM_STR to varchar(4096) is supported****
****Conversion from PDO::PARAM_LOB to varchar(4096) is supported****

Testing varchar(8000):
****Conversion from PDO::PARAM_BOOL to varchar(8000) is supported****
****Conversion from PDO::PARAM_INT to varchar(8000) is supported****
****Conversion from PDO::PARAM_STR to varchar(8000) is supported****
****Conversion from PDO::PARAM_LOB to varchar(8000) is supported****

Testing varchar(max):
****Conversion from PDO::PARAM_BOOL to varchar(max) is supported****
****Conversion from PDO::PARAM_INT to varchar(max) is supported****
****Conversion from PDO::PARAM_STR to varchar(max) is supported****
****Conversion from PDO::PARAM_LOB to varchar(max) is supported****

Testing varchar(max):
****Conversion from PDO::PARAM_BOOL to varchar(max) is supported****
****Conversion from PDO::PARAM_INT to varchar(max) is supported****
****Conversion from PDO::PARAM_STR to varchar(max) is supported****
****Conversion from PDO::PARAM_LOB to varchar(max) is supported****

Testing varchar(max):
****Conversion from PDO::PARAM_BOOL to varchar(max) is supported****
****Conversion from PDO::PARAM_INT to varchar(max) is supported****
****Conversion from PDO::PARAM_STR to varchar(max) is supported****
****Conversion from PDO::PARAM_LOB to varchar(max) is supported****

Testing varchar(max):
****Conversion from PDO::PARAM_BOOL to varchar(max) is supported****
****Conversion from PDO::PARAM_INT to varchar(max) is supported****
****Conversion from PDO::PARAM_STR to varchar(max) is supported****
****Conversion from PDO::PARAM_LOB to varchar(max) is supported****

Testing varchar(max):
****Conversion from PDO::PARAM_BOOL to varchar(max) is supported****
****Conversion from PDO::PARAM_INT to varchar(max) is supported****
****Conversion from PDO::PARAM_STR to varchar(max) is supported****
****Conversion from PDO::PARAM_LOB to varchar(max) is supported****

Testing varchar(max):
****Conversion from PDO::PARAM_BOOL to varchar(max) is supported****
****Conversion from PDO::PARAM_INT to varchar(max) is supported****
****Conversion from PDO::PARAM_STR to varchar(max) is supported****
****Conversion from PDO::PARAM_LOB to varchar(max) is supported****