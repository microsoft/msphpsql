--TEST--
Test for inserting encrypted data into binary types columns with different sizes
--DESCRIPTION--
Test implicit conversions between different binary types of different sizes
With Always Encrypted, implicit conversion works if:
1. From input of SQLSRV_SQLTYPE_BINARY(n) to a larger binary(m) column where n <= m
2. From input of SQLSRV_SQLTYPE_BINARY(n) to a larger varbinary(m) column where n <= m (m can be max)
3. From input of SQLSRV_SQLTYPE_VARBINARY(n) to a larger binary(m) column where n <= m
4. From input of SQLSRV_SQLTYPE_VARBINARY(n) to a larger varbinary(m) column where n <= m (m can be max)
Without AlwaysEncrypted, implicit conversion between different binary types and sizes works
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("binary", "varbinary", "varbinary(max)");
$lengths = array(1, 8, 64, 512, 4000);
$sqlTypes = array("SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_VARBINARY('max')");
$sqltypeLengths = $lengths;
$inputValues = array("d", "f");

$conn = AE\connect();
foreach($dataTypes as $dataType) {
    $maxcol = strpos($dataType, "(max)");
    foreach ($lengths as $m) {
        if ($maxcol !== false) {
            $typeFull = $dataType;
        } else {
            $typeFull = "$dataType($m)";
        }
        echo "\nTesting $typeFull:\n";
        
        // create table containing binary(m) or varbinary(m) columns
        $tbname = "test_" . str_replace(array('(', ')'), '', $dataType) . $m;
        $colMetaArr = array(new AE\ColumnMeta($typeFull, "c_det"), new AE\ColumnMeta($typeFull, "c_rand", null, false));
        AE\createTable($conn, $tbname, $colMetaArr);
        
        // insert by specifying SQLSRV_SQLTYPE_BINARY(n) or SQLSRV_SQLTYPE_VARBINARY(n)
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
                $inputs = array(new AE\BindParamOption($inputValues[0], null, "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)", $sqltypeFull),
                                new AE\BindParamOption($inputValues[1], null, "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)", $sqltypeFull));
                $r;
                $stmt = AE\insertRow($conn, $tbname, array("c_det" => $inputs[0], "c_rand" => $inputs[1]), $r, AE\INSERT_PREPARE_PARAMS);
                
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
                        $sql = "SELECT c_det, c_rand FROM $tbname";
                        $stmt = sqlsrv_query($conn, $sql);
                        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                        if (trim($row['c_det']) != $inputValues[0] || trim($row['c_rand']) != $inputValues[1]) {
                            echo "Conversion from $sqltypeFull to $typeFull causes data corruption\n";
                        }
                    }
                // check the case when SQLSRV_SQLTYPE length (n) is less than or equal to the column length (m)
                // should work with AE or non AE
                } else {
                    if ($r === false) {
                        echo "Conversion from $sqltypeFull to $typeFull should be supported\n";
                    } else {
                        $sql = "SELECT c_det, c_rand FROM $tbname";
                        $stmt = sqlsrv_query($conn, $sql);
                        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                        if (trim($row['c_det']) == $inputValues[0] || trim($row['c_rand']) == $inputValues[1]) {
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
Testing binary(1):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to binary(1) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to binary(1) is supported****

Testing binary(8):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to binary(8) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to binary(8) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to binary(8) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to binary(8) is supported****

Testing binary(64):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to binary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to binary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to binary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to binary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to binary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to binary(64) is supported****

Testing binary(512):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to binary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to binary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to binary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to binary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to binary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to binary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to binary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to binary(512) is supported****

Testing binary(4000):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(4000) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to binary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(4000) to binary(4000) is supported****

Testing varbinary(1):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(1) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(1) is supported****

Testing varbinary(8):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(8) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(8) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(8) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(8) is supported****

Testing varbinary(64):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to varbinary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(64) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to varbinary(64) is supported****

Testing varbinary(512):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to varbinary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to varbinary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to varbinary(512) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to varbinary(512) is supported****

Testing varbinary(4000):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(4000) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to varbinary(4000) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(4000) to varbinary(4000) is supported****

Testing varbinary(max):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****

Testing varbinary(max):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****

Testing varbinary(max):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****

Testing varbinary(max):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****

Testing varbinary(max):
****Conversion from SQLSRV_SQLTYPE_BINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_BINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(1) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(8) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(64) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(512) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY(4000) to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****
****Conversion from SQLSRV_SQLTYPE_VARBINARY('max') to varbinary(max) is supported****