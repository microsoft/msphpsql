--TEST--
Test for inserting encrypted data of nchar types with different sizes
--DESCRIPTION--
Test implicit conversions between different nchar types of different sizes
With Always Encrypted, implicit conversion works if:
1. From input of SQLSRV_SQLTYPE_NCHAR(n) to a larger nchar(m) column where n <= m
2. From input of SQLSRV_SQLTYPE_NCHAR(n) to a larger nvarchar(m) column where n <= m (m can be max)
3. From input of SQLSRV_SQLTYPE_NVARCHAR(n) to a larger nchar(m) column where n <= m
4. From input of SQLSRV_SQLTYPE_NVARCHAR(n) to a larger nvarchar(m) column where n <= m (m can be max)
Without AlwaysEncrypted, implicit conversion between different binary types and sizes works
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("nchar", "nvarchar", "nvarchar(max)");
$lengths = array(1, 8, 64, 512, 4000);
$sqlTypes = array("SQLSRV_SQLTYPE_NCHAR", "SQLSRV_SQLTYPE_NVARCHAR", "SQLSRV_SQLTYPE_NVARCHAR('max')");
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
            
        // create table containing nchar(m) or nvarchar(m) columns
        // only one column is created because a row has a limitation of 8060 bytes
        // for lengths 4096 and 8000, cannot create 2 columns as it will exceed the maximum row sizes
        // for AE, only testing randomized here, deterministic is tested in the char test
        $tbname = "test_" . str_replace(array('(', ')'), '', $dataType) . $m;
        $colMetaArr = array(new AE\ColumnMeta($typeFull, "c1", null, false));
        AE\createTable($conn, $tbname, $colMetaArr);
            
        // insert by specifying SQLSRV_SQLTYPE_NCHAR(n) or SQLSRV_SQLTYPE_NVARCHAR(n)
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
                // if SQLSRV_SQLTYPE_NVARCHAR(max) ($maxsqltype), no conversion is supported except if the column is also max ($maxcol)
                // if column is max ($maxcol), all conversions are supported
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
                            echo "Conversions from $sqltypeFull to $typeFull should be supported\n";
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
Testing nchar(1):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nchar(1) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nchar(1) is supported****

Testing nchar(8):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nchar(8) is supported****

Testing nchar(64):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nchar(64) is supported****

Testing nchar(512):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nchar(512) is supported****

Testing nchar(4000):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(4000) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(4000) to nchar(4000) is supported****

Testing nvarchar(1):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(1) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(1) is supported****

Testing nvarchar(8):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(8) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(8) is supported****

Testing nvarchar(64):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nvarchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(64) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nvarchar(64) is supported****

Testing nvarchar(512):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nvarchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nvarchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nvarchar(512) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nvarchar(512) is supported****

Testing nvarchar(4000):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(4000) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nvarchar(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(4000) to nvarchar(4000) is supported****

Testing nvarchar(max):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****

Testing nvarchar(max):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****

Testing nvarchar(max):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****

Testing nvarchar(max):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****

Testing nvarchar(max):
****Conversion from SQLSRV_SQLTYPE_NCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(1) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(8) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(64) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(512) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR(4000) to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****
****Conversion from SQLSRV_SQLTYPE_NVARCHAR('max') to nvarchar(max) is supported****