--TEST--
Test for retrieving encrypted data of binary types with different sizes as output parameters
--DESCRIPTION--
Test implicit conversions between different binary types of different sizes
With Always Encrypted, implicit conversion works if:
1. From a binary(m) column to a SQLSRV_SQLTYPE_BINARY(n) output parameter where m == n
2. From a binary(m) column to a SQLSRV_SQLTYPE_VARBINARY(n) output parameter where m == n
3. From a varbinary(m) column to a SQLSRV_SQLTYPE_BINARY(n) output parameter where m == n
4. From a varbinary(m) column to a SQLSRV_SQLTYPE_VARBINARY(n) output parameter where m == n
Without AlwaysEncrypted, implicit conversion works if:
1. From a binary(m) column to a SQLSRV_SQLTYPE_BINARY(n) output parameter where m, n == any value
2. From a binary(m) column to a SQLSRV_SQLTYPE_VARBINARY(n) output parameter where m <= n (exclude SQLSRV_SQLTYPE_VARBINARY('max'))
3. From a varbinary(m) column to a SQLSRV_SQLTYPE_BINARY(n) output parameter where m, n == any value
4. From a varbinary(m) column to a SQLSRV_SQLTYPE_VARBINARY(n) output parameter where m, n == any value
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("binary", "varbinary", "varbinary(max)");
$lengths = array(1, 8, 64, 512, 4000);
$sqlTypes = array("SQLSRV_SQLTYPE_BINARY", "SQLSRV_SQLTYPE_VARBINARY", "SQLSRV_SQLTYPE_VARBINARY('max')");
$sqltypeLengths = $lengths;
$directions = array("SQLSRV_PARAM_OUT", "SQLSRV_PARAM_INOUT");
$inputValues = array("d", "f");

$conn = AE\connect();
foreach ($dataTypes as $dataType) {
    $maxcol = strpos($dataType, "(max)");
    foreach ($lengths as $m) {
        if ($maxcol) {
            $typeFull = $dataType;
        } else {
            $typeFull = "$dataType($m)";
        }
        echo "\nTesting $typeFull:\n";
            
        // create and populate table containing binary(m) or varbinary(m) columns
        $tbname = "test_" . str_replace(array('(', ')'), '', $dataType) . $m;
        $colMetaArr = array(new AE\ColumnMeta($typeFull, "c_det"), new AE\ColumnMeta($typeFull, "c_rand", null, false));
        AE\createTable($conn, $tbname, $colMetaArr);
        $inputs = array(new AE\BindParamOption($inputValues[0], null, "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)"),
                                new AE\BindParamOption($inputValues[1], null, "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)"));
        $r;
        $stmt = AE\insertRow($conn, $tbname, array($colMetaArr[0]->colName => $inputs[0], $colMetaArr[1]->colName => $inputs[1]), $r, AE\INSERT_PREPARE_PARAMS);
        
        // create a stored procedure and sql string for calling the stored procedure
        $spname = 'selectAllColumns';
        createProc($conn, $spname, "@c_det $typeFull OUTPUT, @c_rand $typeFull OUTPUT", "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname");
        $sql = AE\getCallProcSqlPlaceholders($spname, 2);
        
        // retrieve by specifying SQLSRV_SQLTYPE_BINARY(n) or SQLSRV_SQLTYPE_VARBINARY(n) as SQLSRV_PARAM_OUT or SQLSRV_PARAM_INOUT
        foreach ($directions as $dir) {
            echo "Testing as $dir:\n";
            foreach ($sqlTypes as $sqlType) {
                $maxsqltype = strpos($sqlType, "max");
                foreach ($sqltypeLengths as $n) {
                    $sqltypeconst;
                    $sqltypeFull;
                    if ($maxsqltype) {
                        $sqltypeconst = SQLSRV_SQLTYPE_VARBINARY('max');
                        $sqltypeFull = $sqlType;
                    } else {
                        $sqltypeconst = call_user_func($sqlType, $n);
                        $sqltypeFull = "$sqlType($n)";
                    }
                    
                    $c_detOut = '';
                    $c_randOut = '';
                    $stmt = sqlsrv_prepare($conn, $sql, array(array(&$c_detOut, constant($dir), SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), $sqltypeconst), array(&$c_randOut, constant($dir), SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY), $sqltypeconst)));
                    $r = sqlsrv_execute($stmt);
                    
                    // check the case when SQLSRV_SQLTYPE length (n) is not the same as the column length (m)
                    // with AE: should not work
                    // without AE: should work, except when a SQLSRV_SQLTYPE_VARBINARY length (n) is less than a binary column length (m) for SQLSRV_PARAM_OUT
                    if (($n != $m || $maxsqltype || $maxcol) && !($maxcol && $maxsqltype)) {
                        if (AE\isDataEncrypted()) {
                            if ($r !== false) {
                                echo "AE: Conversion from $typeFull to output $sqltypeFull should not be supported\n";
                            } else {
                                if (sqlsrv_errors()[0]['SQLSTATE'] != "22018") {
                                    echo "AE: Conversion from $typeFull to output $sqltypeFull expects an operand type clash error, actual error is incorrect\n";
                                    var_dump(sqlsrv_errors());
                                }
                            }
                        } else {
                            if (!AE\isColEncrypted() && strpos($sqltypeFull, "VARBINARY") !== false && $dataType == "binary" && $m > $n  && strpos($sqltypeFull, "max") === false && $dir == "SQLSRV_PARAM_OUT") {
                                if ($r !== false) {
                                    echo "Conversions from $typeFull to output $sqltypeFull should not be supported\n";
                                }
                            } else {
                                if ($r === false) {
                                    if (strpos($sqltypeFull, "VARBINARY") !== false || $dataType != "binary" || $m <= $n) {
                                        echo "Conversions from $typeFull to output $sqltypeFull should be supported\n";
                                    }
                                }
                                if (trim($c_detOut) != $inputValues[0] || trim($c_randOut) != $inputValues[1]) {
                                    echo "Conversion from $typeFull to output $sqltypeFull causes data corruption\n";
                                }
                            }
                        }
                    // check the case then SQLSRV_SQLTYPE length (n) is the same as the column length (m)
                    // should work with AE or non AE
                    } else {
                        if ($r === false) {
                            echo "Conversion from $typeFull to output $sqltypeFull should be supported\n";
                            var_dump(sqlsrv_errors());
                        } else {
                            if (trim($c_detOut) == $inputValues[0] && trim($c_randOut) == $inputValues[1]) {
                                echo "****Conversion from $typeFull to output $sqltypeFull is supported****\n";
                           } else {
                                echo "Conversion from $typeFull to output $sqltypeFull causes data corruption\n";
                           }
                        }
                    }
                    // cleanup
                    sqlsrv_free_stmt($stmt);
                }
            }
        }
        dropProc($conn, $spname);
        dropTable($conn, $tbname);
    }
}
sqlsrv_close($conn);
                
?>
--EXPECT--
Testing binary(1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from binary(1) to output SQLSRV_SQLTYPE_BINARY(1) is supported****
****Conversion from binary(1) to output SQLSRV_SQLTYPE_VARBINARY(1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from binary(1) to output SQLSRV_SQLTYPE_BINARY(1) is supported****
****Conversion from binary(1) to output SQLSRV_SQLTYPE_VARBINARY(1) is supported****

Testing binary(8):
Testing as SQLSRV_PARAM_OUT:
****Conversion from binary(8) to output SQLSRV_SQLTYPE_BINARY(8) is supported****
****Conversion from binary(8) to output SQLSRV_SQLTYPE_VARBINARY(8) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from binary(8) to output SQLSRV_SQLTYPE_BINARY(8) is supported****
****Conversion from binary(8) to output SQLSRV_SQLTYPE_VARBINARY(8) is supported****

Testing binary(64):
Testing as SQLSRV_PARAM_OUT:
****Conversion from binary(64) to output SQLSRV_SQLTYPE_BINARY(64) is supported****
****Conversion from binary(64) to output SQLSRV_SQLTYPE_VARBINARY(64) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from binary(64) to output SQLSRV_SQLTYPE_BINARY(64) is supported****
****Conversion from binary(64) to output SQLSRV_SQLTYPE_VARBINARY(64) is supported****

Testing binary(512):
Testing as SQLSRV_PARAM_OUT:
****Conversion from binary(512) to output SQLSRV_SQLTYPE_BINARY(512) is supported****
****Conversion from binary(512) to output SQLSRV_SQLTYPE_VARBINARY(512) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from binary(512) to output SQLSRV_SQLTYPE_BINARY(512) is supported****
****Conversion from binary(512) to output SQLSRV_SQLTYPE_VARBINARY(512) is supported****

Testing binary(4000):
Testing as SQLSRV_PARAM_OUT:
****Conversion from binary(4000) to output SQLSRV_SQLTYPE_BINARY(4000) is supported****
****Conversion from binary(4000) to output SQLSRV_SQLTYPE_VARBINARY(4000) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from binary(4000) to output SQLSRV_SQLTYPE_BINARY(4000) is supported****
****Conversion from binary(4000) to output SQLSRV_SQLTYPE_VARBINARY(4000) is supported****

Testing varbinary(1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(1) to output SQLSRV_SQLTYPE_BINARY(1) is supported****
****Conversion from varbinary(1) to output SQLSRV_SQLTYPE_VARBINARY(1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(1) to output SQLSRV_SQLTYPE_BINARY(1) is supported****
****Conversion from varbinary(1) to output SQLSRV_SQLTYPE_VARBINARY(1) is supported****

Testing varbinary(8):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(8) to output SQLSRV_SQLTYPE_BINARY(8) is supported****
****Conversion from varbinary(8) to output SQLSRV_SQLTYPE_VARBINARY(8) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(8) to output SQLSRV_SQLTYPE_BINARY(8) is supported****
****Conversion from varbinary(8) to output SQLSRV_SQLTYPE_VARBINARY(8) is supported****

Testing varbinary(64):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(64) to output SQLSRV_SQLTYPE_BINARY(64) is supported****
****Conversion from varbinary(64) to output SQLSRV_SQLTYPE_VARBINARY(64) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(64) to output SQLSRV_SQLTYPE_BINARY(64) is supported****
****Conversion from varbinary(64) to output SQLSRV_SQLTYPE_VARBINARY(64) is supported****

Testing varbinary(512):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(512) to output SQLSRV_SQLTYPE_BINARY(512) is supported****
****Conversion from varbinary(512) to output SQLSRV_SQLTYPE_VARBINARY(512) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(512) to output SQLSRV_SQLTYPE_BINARY(512) is supported****
****Conversion from varbinary(512) to output SQLSRV_SQLTYPE_VARBINARY(512) is supported****

Testing varbinary(4000):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(4000) to output SQLSRV_SQLTYPE_BINARY(4000) is supported****
****Conversion from varbinary(4000) to output SQLSRV_SQLTYPE_VARBINARY(4000) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(4000) to output SQLSRV_SQLTYPE_BINARY(4000) is supported****
****Conversion from varbinary(4000) to output SQLSRV_SQLTYPE_VARBINARY(4000) is supported****

Testing varbinary(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****

Testing varbinary(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****

Testing varbinary(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****

Testing varbinary(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****

Testing varbinary(max):
Testing as SQLSRV_PARAM_OUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****
****Conversion from varbinary(max) to output SQLSRV_SQLTYPE_VARBINARY('max') is supported****