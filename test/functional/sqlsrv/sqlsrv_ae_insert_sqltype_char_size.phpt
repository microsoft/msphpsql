--TEST--
Test for inserting encrypted data of char types with different sizes
--DESCRIPTION--
Test implicit conversions between different char types of different sizes
With Always Encrypted, implicit conversion works if:
1. From input of SQLSRV_SQLTYPE_CHAR(n) to a larger char(m) column where n <= m
2. From input of SQLSRV_SQLTYPE_CHAR(n) to a larger varchar(m) column where n <= m (m can be max)
3. From input of SQLSRV_SQLTYPE_VARCHAR(n) to a larger char(m) column where n <= m
4. From input of SQLSRV_SQLTYPE_VARCHAR(n) to a larger varchar(m) column where n <= m (m can be max)
Without AlwaysEncrypted, implicit conversion between different binary types and sizes works
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("char", "varchar", "varchar(max)");
$lengths = array(1, 8, 64, 512, 4096, 8000);
$sqlTypes = array("SQLSRV_SQLTYPE_CHAR", "SQLSRV_SQLTYPE_VARCHAR", "SQLSRV_SQLTYPE_VARCHAR('max')");
$sqltypeLengths = $lengths;
$inputValue = "d";

$conn = AE\connect();
foreach($dataTypes as $dataType) {
    $maxcol = strpos($dataType, "(max)");
    foreach($lengths as $m) {
        if ($maxcol !== false) {
            $typeFull = $dataType;
        } else {
            $typeFull = "$dataType($m)";
        }
        echo "\nTesting $typeFull:\n";
            
        // create table containing char(m) or varchar(m) columns
        // only one column is created because a row has a limitation of 8060 bytes
        // for lengths 4096 and 8000, cannot create 2 columns as it will exceed the maximum row sizes
        // for AE, only testing deterministic here, randomized is tested in the nchar test
        $tbname = "test_" . str_replace(array('(', ')'), '', $dataType) . $m;
        $colMetaArr = array(new AE\ColumnMeta($typeFull, "c1"));
        AE\createTable($conn, $tbname, $colMetaArr);
            
        // insert by specifying SQLSRV_SQLTYPE_CHAR(n) or SQLSRV_SQLTYPE_VARCHAR(n)
        // with AE, should be successful as long as the SQLSRV_SQLTYPE length (n) is smaller than the column length (m)
        foreach($sqlTypes as $sqlType) {
            $maxsqltype = strpos($sqlType, "max");
            foreach($sqltypeLengths as $n) {
                if ($maxsqltype !== false) {
                    $sqltypeFull = $sqlType;
                } else {
                    $sqltypeFull = "$sqlType($n)";
                }
                    
                //insert a row
                $input = new AE\BindParamOption($inputValue, null, null, $sqltypeFull);
                $r;
                $stmt = AE\insertRow($conn, $tbname, array("c1" => $input), $r, AE\INSERT_PREPARE_PARAMS);
                    
                // check the case when SQLSRV_SQLTYPE length (n) is greater than the column length (m)
                // with AE: should not work
                // without AE: should work
                if (($n > $m || $maxsqltype) && !$maxcol) {
                    if (AE\isDataEncrypted()) {
                        if ($r !== false) {
                            echo "AE: Conversion from $sqltypeFull to $typeFull should not be supported\n";
                        } else {
                            if (sqlsrv_errors()[0]['SQLSTATE'] != "22018") {
                                echo "AE: Conversion from $sqltypeFull to $typeFull expects an operand type clash error, actual error is incorrect\n";
                                var_dump(sqlsrv_errors());
                            }
                        }
                    } else {
                        if ($r === false) {
                            echo "Conversion from $sqltypeFull to $typeFull should be supported\n";
                        }
                        $sql = "SELECT c1 FROM $tbname";
                        $stmt = sqlsrv_query($conn, $sql);
                        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                        if (trim($row['c1']) != $inputValue) {
                            echo "Conversion from $sqltypeFull to $typeFull causes data corruption\n";
                        }
                    }
                // check the case when SQLSRV_SQLTYPE length (n) is less than or equal to the column length (m)
                // should work with AE or non AE
                } else {
                    if ($r === false) {
                        echo "Conversion from $sqltypeFull to $typeFull should be supported\n";
                    } else {
                        $sql = "SELECT c1 FROM $tbname";
                        $stmt = sqlsrv_query($conn, $sql);
                        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                        if (trim($row['c1']) == $inputValue) {
                            echo "****Conversion from $sqltypeFull to $typeFull is supported****\n";
                        } else {
                            echo "Conversion from $sqltypeFull to $typeFull causes data corruption\n";
                        }     
                    }
                }
                // cleanup
                sqlsrv_free_stmt($stmt);
                sqlsrv_query($conn, "TRUNCATE TABLE $tbname");
            }
        }
        dropTable($conn, $tbname);
    }
}
sqlsrv_close($conn);    
           
?>
--EXPECT--
Testing char(1):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to char(1) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to char(1) is supported****

Testing char(8):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to char(8) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to char(8) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to char(8) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to char(8) is supported****

Testing char(64):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to char(64) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to char(64) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to char(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to char(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to char(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to char(64) is supported****

Testing char(512):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to char(512) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to char(512) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to char(512) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to char(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to char(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to char(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to char(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to char(512) is supported****

Testing char(4096):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to char(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to char(4096) is supported****

Testing char(8000):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8000) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to char(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8000) to char(8000) is supported****

Testing varchar(1):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(1) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(1) is supported****

Testing varchar(8):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(8) is supported****

Testing varchar(64):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(64) is supported****

Testing varchar(512):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(512) is supported****

Testing varchar(4096):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(4096) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to varchar(4096) is supported****

Testing varchar(8000):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8000) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to varchar(8000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8000) to varchar(8000) is supported****

Testing varchar(max):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****

Testing varchar(max):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****

Testing varchar(max):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****

Testing varchar(max):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****

Testing varchar(max):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****

Testing varchar(max):
****Conversion from SQLSRV_SQLTYPE_CHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_CHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(1) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(64) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(512) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(4096) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR(8000) to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARCHAR('max') to varchar(max) is supported****