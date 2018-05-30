--TEST--
Test for retrieving encrypted data of decimal types with different precisions and scales as output parameters
--DESCRIPTION--
Test implicit conversions between different precisions and scales
With Always Encrypted, no implicit conversion works for decimal datatypes, the precision and scale specified in the SQLSRV_SQLTYPE must be identical to the precision and scale defined in the column
Without AlwaysEncrypted, implicit conversion between precisions or scales works if:
1. From a decimal(m1, m2) column to a SQLSRV_SQLTYPE_DECIMAL(n1, n2) output parameter where n1 - n2 >= m1 - m2
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("decimal", "numeric");
$precisions = array(1 => array(0, 1), 
                    4 => array(0, 1, 4), 
                    16 => array(0, 1, 4, 16),
                    19 => array(0, 1, 4, 16, 19),
                    38 => array(0, 1, 4, 16, 38));
$sqlTypes = array("SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC");
$sqltypePrecisions = $precisions;
$inputValuesInit = array(92233720368547758089223372036854775808, -92233720368547758089223372036854775808);
$maxInPrecision = 38;
$directions = array("SQLSRV_PARAM_OUT", "SQLSRV_PARAM_INOUT");

$conn = AE\connect();

foreach ($dataTypes as $dataType) {
    foreach ($precisions as $m1 => $inScales) {
        foreach ($inScales as $m2) {
            // change the number of integers in the input values to be $m1 - $m2
            $precDiff = $maxInPrecision - ($m1 - $m2);
            $inputValues = $inputValuesInit;
            foreach ($inputValues as &$inputValue) {
                $inputValue = $inputValue / pow(10, $precDiff);
            }
            $typeFull = "$dataType($m1, $m2)";
            echo "\nTesting $typeFull:\n";
            
            // create and populate table containing decimal(m1, m2) or numeric(m1, m2) columns
            $tbname = "test_" . $dataType . $m1 . $m2;
            $colMetaArr = array(new AE\ColumnMeta($typeFull, "c_det"), new AE\ColumnMeta($typeFull, "c_rand", null, false));
            AE\createTable($conn, $tbname, $colMetaArr);
            $stmt = AE\insertRow($conn, $tbname, array($colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1]));
    
            // create a stored procedure and sql string for calling the stored procedure
            $spname = 'selectAllColumns';
            createProc($conn, $spname, "@c_det $typeFull OUTPUT, @c_rand $typeFull OUTPUT", "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname");
            $sql = AE\getCallProcSqlPlaceholders($spname, 2);
            
            // retrieve by specifying SQLSRV_SQLTYPE_DECIMAL(n1, n2) or SQLSRV_SQLTYPE_NUMERIC(n1, n2) as SQLSRV_PARAM_OUT or SQLSRV_PARAM_INOUT
            foreach ($directions as $dir) {
                echo "Testing as $dir:\n";
                foreach ($sqlTypes as $sqlType) {
                    foreach ($sqltypePrecisions as $n1 => $sqltypeScales) {
                        foreach ($sqltypeScales as $n2) {
                        
                            // compute the epsilon for comparing doubles
                            // float in PHP only has a precision of roughtly 14 digits: http://php.net/manual/en/language.types.float.php
                            // the smaller precision and scale (n1 and n2 vs m1 and m2) take precedence
                            $epsilon;
                            $smallerprec = min($m1, $n1);
                            $smallerscale = min($m2, $n2);
                            if ($smallerprec < 14) {
                                $epsilon = pow(10, $smallerscale * -1);
                            } else {
                                $numint = $smallerprec - $smallerscale;
                                if ($numint < 14) {
                                    $epsilon = pow(10, (14 - $numint) * -1);
                                } else {
                                    $epsilon = pow(10, $numint - 14);
                                }
                            }
                        
                            $sqltypeFull = "$sqlType($n1, $n2)";
                            $sqltypeconst = call_user_func($sqlType, $n1, $n2);
                            
                            $c_detOut = 0.0;
                            $c_randOut = 0.0;
                            $stmt = sqlsrv_prepare($conn, $sql, array(array(&$c_detOut, constant($dir), null, $sqltypeconst), array(&$c_randOut, constant($dir), null, $sqltypeconst)));
                            $r = sqlsrv_execute($stmt);
                            
                            // check the case when the SQLSRV_SQLTYPE precision (n1) is not the same as the column precision (m1)
                            // or the SQLSRV_SQLTYPE scale (n2) is not the same as the column precision (m2)
                            // with AE: should not work
                            // without AE: should not work if n1 - n2 < m1 - m2
                            if ($n1 != $m1 || $n2 != $m2) {
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
                                    if ($n1 - $n2 < $m1 - $m2) {
                                        if ($r !== false) {
                                            echo "Conversion from $typeFull to output $sqltypeFull should not be supported\n";
                                        }
                                    } else {
                                        if ($r === false) {
                                            echo "Conversion from $typeFull to output $sqltypeFull should be supported\n";
                                        } else {
                                            if (abs($c_detOut - $inputValues[0]) > $epsilon || abs($c_randOut - $inputValues[1]) > $epsilon) {
                                                echo "Conversion from $typeFull to output $sqltypeFull causes data corruption\n";
                                            }
                                        }
                                    }
                                }
                            // check the case when the SQLSRV_SQLTYPE precision (n1) and scale (n2) are the same as the column precision (m1) and scale (m2)
                            // should work with AE or non AE
                            } else {
                                if ($r === false) {
                                    echo "Conversion from $typeFull to output $sqltypeFull should be supported\n";
                                } else {
                                    if (abs($c_detOut - $inputValues[0]) < $epsilon && abs($c_randOut - $inputValues[1]) < $epsilon) {
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
            }
            dropProc($conn, $spname);
            dropTable($conn, $tbname);
        }
    }
}
sqlsrv_close($conn);
?>
--EXPECT--
Testing decimal(1, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(1, 0) to output SQLSRV_SQLTYPE_DECIMAL(1, 0) is supported****
****Conversion from decimal(1, 0) to output SQLSRV_SQLTYPE_NUMERIC(1, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(1, 0) to output SQLSRV_SQLTYPE_DECIMAL(1, 0) is supported****
****Conversion from decimal(1, 0) to output SQLSRV_SQLTYPE_NUMERIC(1, 0) is supported****

Testing decimal(1, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(1, 1) to output SQLSRV_SQLTYPE_DECIMAL(1, 1) is supported****
****Conversion from decimal(1, 1) to output SQLSRV_SQLTYPE_NUMERIC(1, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(1, 1) to output SQLSRV_SQLTYPE_DECIMAL(1, 1) is supported****
****Conversion from decimal(1, 1) to output SQLSRV_SQLTYPE_NUMERIC(1, 1) is supported****

Testing decimal(4, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(4, 0) to output SQLSRV_SQLTYPE_DECIMAL(4, 0) is supported****
****Conversion from decimal(4, 0) to output SQLSRV_SQLTYPE_NUMERIC(4, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(4, 0) to output SQLSRV_SQLTYPE_DECIMAL(4, 0) is supported****
****Conversion from decimal(4, 0) to output SQLSRV_SQLTYPE_NUMERIC(4, 0) is supported****

Testing decimal(4, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(4, 1) to output SQLSRV_SQLTYPE_DECIMAL(4, 1) is supported****
****Conversion from decimal(4, 1) to output SQLSRV_SQLTYPE_NUMERIC(4, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(4, 1) to output SQLSRV_SQLTYPE_DECIMAL(4, 1) is supported****
****Conversion from decimal(4, 1) to output SQLSRV_SQLTYPE_NUMERIC(4, 1) is supported****

Testing decimal(4, 4):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(4, 4) to output SQLSRV_SQLTYPE_DECIMAL(4, 4) is supported****
****Conversion from decimal(4, 4) to output SQLSRV_SQLTYPE_NUMERIC(4, 4) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(4, 4) to output SQLSRV_SQLTYPE_DECIMAL(4, 4) is supported****
****Conversion from decimal(4, 4) to output SQLSRV_SQLTYPE_NUMERIC(4, 4) is supported****

Testing decimal(16, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(16, 0) to output SQLSRV_SQLTYPE_DECIMAL(16, 0) is supported****
****Conversion from decimal(16, 0) to output SQLSRV_SQLTYPE_NUMERIC(16, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(16, 0) to output SQLSRV_SQLTYPE_DECIMAL(16, 0) is supported****
****Conversion from decimal(16, 0) to output SQLSRV_SQLTYPE_NUMERIC(16, 0) is supported****

Testing decimal(16, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(16, 1) to output SQLSRV_SQLTYPE_DECIMAL(16, 1) is supported****
****Conversion from decimal(16, 1) to output SQLSRV_SQLTYPE_NUMERIC(16, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(16, 1) to output SQLSRV_SQLTYPE_DECIMAL(16, 1) is supported****
****Conversion from decimal(16, 1) to output SQLSRV_SQLTYPE_NUMERIC(16, 1) is supported****

Testing decimal(16, 4):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(16, 4) to output SQLSRV_SQLTYPE_DECIMAL(16, 4) is supported****
****Conversion from decimal(16, 4) to output SQLSRV_SQLTYPE_NUMERIC(16, 4) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(16, 4) to output SQLSRV_SQLTYPE_DECIMAL(16, 4) is supported****
****Conversion from decimal(16, 4) to output SQLSRV_SQLTYPE_NUMERIC(16, 4) is supported****

Testing decimal(16, 16):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(16, 16) to output SQLSRV_SQLTYPE_DECIMAL(16, 16) is supported****
****Conversion from decimal(16, 16) to output SQLSRV_SQLTYPE_NUMERIC(16, 16) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(16, 16) to output SQLSRV_SQLTYPE_DECIMAL(16, 16) is supported****
****Conversion from decimal(16, 16) to output SQLSRV_SQLTYPE_NUMERIC(16, 16) is supported****

Testing decimal(19, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(19, 0) to output SQLSRV_SQLTYPE_DECIMAL(19, 0) is supported****
****Conversion from decimal(19, 0) to output SQLSRV_SQLTYPE_NUMERIC(19, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(19, 0) to output SQLSRV_SQLTYPE_DECIMAL(19, 0) is supported****
****Conversion from decimal(19, 0) to output SQLSRV_SQLTYPE_NUMERIC(19, 0) is supported****

Testing decimal(19, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(19, 1) to output SQLSRV_SQLTYPE_DECIMAL(19, 1) is supported****
****Conversion from decimal(19, 1) to output SQLSRV_SQLTYPE_NUMERIC(19, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(19, 1) to output SQLSRV_SQLTYPE_DECIMAL(19, 1) is supported****
****Conversion from decimal(19, 1) to output SQLSRV_SQLTYPE_NUMERIC(19, 1) is supported****

Testing decimal(19, 4):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(19, 4) to output SQLSRV_SQLTYPE_DECIMAL(19, 4) is supported****
****Conversion from decimal(19, 4) to output SQLSRV_SQLTYPE_NUMERIC(19, 4) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(19, 4) to output SQLSRV_SQLTYPE_DECIMAL(19, 4) is supported****
****Conversion from decimal(19, 4) to output SQLSRV_SQLTYPE_NUMERIC(19, 4) is supported****

Testing decimal(19, 16):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(19, 16) to output SQLSRV_SQLTYPE_DECIMAL(19, 16) is supported****
****Conversion from decimal(19, 16) to output SQLSRV_SQLTYPE_NUMERIC(19, 16) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(19, 16) to output SQLSRV_SQLTYPE_DECIMAL(19, 16) is supported****
****Conversion from decimal(19, 16) to output SQLSRV_SQLTYPE_NUMERIC(19, 16) is supported****

Testing decimal(19, 19):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(19, 19) to output SQLSRV_SQLTYPE_DECIMAL(19, 19) is supported****
****Conversion from decimal(19, 19) to output SQLSRV_SQLTYPE_NUMERIC(19, 19) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(19, 19) to output SQLSRV_SQLTYPE_DECIMAL(19, 19) is supported****
****Conversion from decimal(19, 19) to output SQLSRV_SQLTYPE_NUMERIC(19, 19) is supported****

Testing decimal(38, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(38, 0) to output SQLSRV_SQLTYPE_DECIMAL(38, 0) is supported****
****Conversion from decimal(38, 0) to output SQLSRV_SQLTYPE_NUMERIC(38, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(38, 0) to output SQLSRV_SQLTYPE_DECIMAL(38, 0) is supported****
****Conversion from decimal(38, 0) to output SQLSRV_SQLTYPE_NUMERIC(38, 0) is supported****

Testing decimal(38, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(38, 1) to output SQLSRV_SQLTYPE_DECIMAL(38, 1) is supported****
****Conversion from decimal(38, 1) to output SQLSRV_SQLTYPE_NUMERIC(38, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(38, 1) to output SQLSRV_SQLTYPE_DECIMAL(38, 1) is supported****
****Conversion from decimal(38, 1) to output SQLSRV_SQLTYPE_NUMERIC(38, 1) is supported****

Testing decimal(38, 4):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(38, 4) to output SQLSRV_SQLTYPE_DECIMAL(38, 4) is supported****
****Conversion from decimal(38, 4) to output SQLSRV_SQLTYPE_NUMERIC(38, 4) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(38, 4) to output SQLSRV_SQLTYPE_DECIMAL(38, 4) is supported****
****Conversion from decimal(38, 4) to output SQLSRV_SQLTYPE_NUMERIC(38, 4) is supported****

Testing decimal(38, 16):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(38, 16) to output SQLSRV_SQLTYPE_DECIMAL(38, 16) is supported****
****Conversion from decimal(38, 16) to output SQLSRV_SQLTYPE_NUMERIC(38, 16) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(38, 16) to output SQLSRV_SQLTYPE_DECIMAL(38, 16) is supported****
****Conversion from decimal(38, 16) to output SQLSRV_SQLTYPE_NUMERIC(38, 16) is supported****

Testing decimal(38, 38):
Testing as SQLSRV_PARAM_OUT:
****Conversion from decimal(38, 38) to output SQLSRV_SQLTYPE_DECIMAL(38, 38) is supported****
****Conversion from decimal(38, 38) to output SQLSRV_SQLTYPE_NUMERIC(38, 38) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from decimal(38, 38) to output SQLSRV_SQLTYPE_DECIMAL(38, 38) is supported****
****Conversion from decimal(38, 38) to output SQLSRV_SQLTYPE_NUMERIC(38, 38) is supported****

Testing numeric(1, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(1, 0) to output SQLSRV_SQLTYPE_DECIMAL(1, 0) is supported****
****Conversion from numeric(1, 0) to output SQLSRV_SQLTYPE_NUMERIC(1, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(1, 0) to output SQLSRV_SQLTYPE_DECIMAL(1, 0) is supported****
****Conversion from numeric(1, 0) to output SQLSRV_SQLTYPE_NUMERIC(1, 0) is supported****

Testing numeric(1, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(1, 1) to output SQLSRV_SQLTYPE_DECIMAL(1, 1) is supported****
****Conversion from numeric(1, 1) to output SQLSRV_SQLTYPE_NUMERIC(1, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(1, 1) to output SQLSRV_SQLTYPE_DECIMAL(1, 1) is supported****
****Conversion from numeric(1, 1) to output SQLSRV_SQLTYPE_NUMERIC(1, 1) is supported****

Testing numeric(4, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(4, 0) to output SQLSRV_SQLTYPE_DECIMAL(4, 0) is supported****
****Conversion from numeric(4, 0) to output SQLSRV_SQLTYPE_NUMERIC(4, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(4, 0) to output SQLSRV_SQLTYPE_DECIMAL(4, 0) is supported****
****Conversion from numeric(4, 0) to output SQLSRV_SQLTYPE_NUMERIC(4, 0) is supported****

Testing numeric(4, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(4, 1) to output SQLSRV_SQLTYPE_DECIMAL(4, 1) is supported****
****Conversion from numeric(4, 1) to output SQLSRV_SQLTYPE_NUMERIC(4, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(4, 1) to output SQLSRV_SQLTYPE_DECIMAL(4, 1) is supported****
****Conversion from numeric(4, 1) to output SQLSRV_SQLTYPE_NUMERIC(4, 1) is supported****

Testing numeric(4, 4):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(4, 4) to output SQLSRV_SQLTYPE_DECIMAL(4, 4) is supported****
****Conversion from numeric(4, 4) to output SQLSRV_SQLTYPE_NUMERIC(4, 4) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(4, 4) to output SQLSRV_SQLTYPE_DECIMAL(4, 4) is supported****
****Conversion from numeric(4, 4) to output SQLSRV_SQLTYPE_NUMERIC(4, 4) is supported****

Testing numeric(16, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(16, 0) to output SQLSRV_SQLTYPE_DECIMAL(16, 0) is supported****
****Conversion from numeric(16, 0) to output SQLSRV_SQLTYPE_NUMERIC(16, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(16, 0) to output SQLSRV_SQLTYPE_DECIMAL(16, 0) is supported****
****Conversion from numeric(16, 0) to output SQLSRV_SQLTYPE_NUMERIC(16, 0) is supported****

Testing numeric(16, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(16, 1) to output SQLSRV_SQLTYPE_DECIMAL(16, 1) is supported****
****Conversion from numeric(16, 1) to output SQLSRV_SQLTYPE_NUMERIC(16, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(16, 1) to output SQLSRV_SQLTYPE_DECIMAL(16, 1) is supported****
****Conversion from numeric(16, 1) to output SQLSRV_SQLTYPE_NUMERIC(16, 1) is supported****

Testing numeric(16, 4):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(16, 4) to output SQLSRV_SQLTYPE_DECIMAL(16, 4) is supported****
****Conversion from numeric(16, 4) to output SQLSRV_SQLTYPE_NUMERIC(16, 4) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(16, 4) to output SQLSRV_SQLTYPE_DECIMAL(16, 4) is supported****
****Conversion from numeric(16, 4) to output SQLSRV_SQLTYPE_NUMERIC(16, 4) is supported****

Testing numeric(16, 16):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(16, 16) to output SQLSRV_SQLTYPE_DECIMAL(16, 16) is supported****
****Conversion from numeric(16, 16) to output SQLSRV_SQLTYPE_NUMERIC(16, 16) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(16, 16) to output SQLSRV_SQLTYPE_DECIMAL(16, 16) is supported****
****Conversion from numeric(16, 16) to output SQLSRV_SQLTYPE_NUMERIC(16, 16) is supported****

Testing numeric(19, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(19, 0) to output SQLSRV_SQLTYPE_DECIMAL(19, 0) is supported****
****Conversion from numeric(19, 0) to output SQLSRV_SQLTYPE_NUMERIC(19, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(19, 0) to output SQLSRV_SQLTYPE_DECIMAL(19, 0) is supported****
****Conversion from numeric(19, 0) to output SQLSRV_SQLTYPE_NUMERIC(19, 0) is supported****

Testing numeric(19, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(19, 1) to output SQLSRV_SQLTYPE_DECIMAL(19, 1) is supported****
****Conversion from numeric(19, 1) to output SQLSRV_SQLTYPE_NUMERIC(19, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(19, 1) to output SQLSRV_SQLTYPE_DECIMAL(19, 1) is supported****
****Conversion from numeric(19, 1) to output SQLSRV_SQLTYPE_NUMERIC(19, 1) is supported****

Testing numeric(19, 4):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(19, 4) to output SQLSRV_SQLTYPE_DECIMAL(19, 4) is supported****
****Conversion from numeric(19, 4) to output SQLSRV_SQLTYPE_NUMERIC(19, 4) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(19, 4) to output SQLSRV_SQLTYPE_DECIMAL(19, 4) is supported****
****Conversion from numeric(19, 4) to output SQLSRV_SQLTYPE_NUMERIC(19, 4) is supported****

Testing numeric(19, 16):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(19, 16) to output SQLSRV_SQLTYPE_DECIMAL(19, 16) is supported****
****Conversion from numeric(19, 16) to output SQLSRV_SQLTYPE_NUMERIC(19, 16) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(19, 16) to output SQLSRV_SQLTYPE_DECIMAL(19, 16) is supported****
****Conversion from numeric(19, 16) to output SQLSRV_SQLTYPE_NUMERIC(19, 16) is supported****

Testing numeric(19, 19):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(19, 19) to output SQLSRV_SQLTYPE_DECIMAL(19, 19) is supported****
****Conversion from numeric(19, 19) to output SQLSRV_SQLTYPE_NUMERIC(19, 19) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(19, 19) to output SQLSRV_SQLTYPE_DECIMAL(19, 19) is supported****
****Conversion from numeric(19, 19) to output SQLSRV_SQLTYPE_NUMERIC(19, 19) is supported****

Testing numeric(38, 0):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(38, 0) to output SQLSRV_SQLTYPE_DECIMAL(38, 0) is supported****
****Conversion from numeric(38, 0) to output SQLSRV_SQLTYPE_NUMERIC(38, 0) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(38, 0) to output SQLSRV_SQLTYPE_DECIMAL(38, 0) is supported****
****Conversion from numeric(38, 0) to output SQLSRV_SQLTYPE_NUMERIC(38, 0) is supported****

Testing numeric(38, 1):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(38, 1) to output SQLSRV_SQLTYPE_DECIMAL(38, 1) is supported****
****Conversion from numeric(38, 1) to output SQLSRV_SQLTYPE_NUMERIC(38, 1) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(38, 1) to output SQLSRV_SQLTYPE_DECIMAL(38, 1) is supported****
****Conversion from numeric(38, 1) to output SQLSRV_SQLTYPE_NUMERIC(38, 1) is supported****

Testing numeric(38, 4):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(38, 4) to output SQLSRV_SQLTYPE_DECIMAL(38, 4) is supported****
****Conversion from numeric(38, 4) to output SQLSRV_SQLTYPE_NUMERIC(38, 4) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(38, 4) to output SQLSRV_SQLTYPE_DECIMAL(38, 4) is supported****
****Conversion from numeric(38, 4) to output SQLSRV_SQLTYPE_NUMERIC(38, 4) is supported****

Testing numeric(38, 16):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(38, 16) to output SQLSRV_SQLTYPE_DECIMAL(38, 16) is supported****
****Conversion from numeric(38, 16) to output SQLSRV_SQLTYPE_NUMERIC(38, 16) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(38, 16) to output SQLSRV_SQLTYPE_DECIMAL(38, 16) is supported****
****Conversion from numeric(38, 16) to output SQLSRV_SQLTYPE_NUMERIC(38, 16) is supported****

Testing numeric(38, 38):
Testing as SQLSRV_PARAM_OUT:
****Conversion from numeric(38, 38) to output SQLSRV_SQLTYPE_DECIMAL(38, 38) is supported****
****Conversion from numeric(38, 38) to output SQLSRV_SQLTYPE_NUMERIC(38, 38) is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from numeric(38, 38) to output SQLSRV_SQLTYPE_DECIMAL(38, 38) is supported****
****Conversion from numeric(38, 38) to output SQLSRV_SQLTYPE_NUMERIC(38, 38) is supported****