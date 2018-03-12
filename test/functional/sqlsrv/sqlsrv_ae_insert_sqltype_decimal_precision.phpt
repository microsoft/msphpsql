--TEST--
Test for inserting encrypted data of decimal types with different precisions and scales
--DESCRIPTION--
Test implicit conversions between different precisions and scales
With Always Encrypted, no implicit conversion works for decimal datatypes, the precision and scale specified in the SQLSRV_SQLTYPE must be identical to the precision and scale defined in the column
Without AlwaysEncrypted, implicit conversion between precisions or scales works if:
1. From input of SQLSRV_SQLTYPE_DECIMAL(n1, n2) to a decimal(m1, m2) column where n1 - n2 > m1 - m2 and
2. where n2 != 0 && m1 != m2
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("decimal", "numeric");
$precisions = array(1 => array(0, 1), 
                    4 => array(0, 1, 4), 
                    16 => array(0, 1, 4, 16), 
                    38 => array(0, 1, 4, 16, 38));
$sqlTypes = array("SQLSRV_SQLTYPE_DECIMAL", "SQLSRV_SQLTYPE_NUMERIC");
$sqltypePrecisions = $precisions;
$inputValuesInit = array(92233720368547758089223372036854775808, -92233720368547758089223372036854775808);
$maxInPrecision = 38;

$conn = AE\connect();

foreach($dataTypes as $dataType) {
    foreach($precisions as $m1 => $inScales) {
        foreach($inScales as $m2) {
            // change the number of integers in the input values to be $m1 - $m2
            $precDiff = $maxInPrecision - ($m1 - $m2);
            $inputValues = $inputValuesInit;
            foreach ($inputValues as &$inputValue) {
                $inputValue = $inputValue / pow(10, $precDiff);
            }
            $typeFull = "$dataType($m1, $m2)";
            echo "\nTesting $typeFull:\n";
            
            // create table containing decimal(m1, m2) or numeric(m1, m2) columns
            $tbname = "test_" . $dataType . $m1 . $m2;
            $colMetaArr = array(new AE\ColumnMeta($typeFull, "c_det"), new AE\ColumnMeta($typeFull, "c_rand", null, false));
            AE\createTable($conn, $tbname, $colMetaArr);
            
            // insert by specifying SQLSRV_SQLTYPE_DECIMAL(n1, n2) or SQLSRV_SQLTYPE_NUMERIC(n1, n2)
            // with AE, should only be successful if the SQLSRV_SQLTYPE precision (n1) and scale (n2) are the same as the column precision (m1) and scale (m2)
            foreach($sqlTypes as $sqlType) {
                foreach($sqltypePrecisions as $n1 => $sqltypeScales) {
                    foreach($sqltypeScales as $n2) {
                    
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
                        
                        //insert a row
                        $inputs = array(new AE\BindParamOption((string)$inputValues[0], null, null, $sqltypeFull),
                                        new AE\BindParamOption((string)$inputValues[1], null, null, $sqltypeFull));
                        $r;
                        $stmt = AE\insertRow($conn, $tbname, array("c_det" => $inputs[0], "c_rand" => $inputs[1]), $r, AE\INSERT_PREPARE_PARAMS);
                        
                        // check the case when the SQLSRV_SQLTYPE precision (n1) is not the same as the column precision (m1)
                        // or the SQLSRV_SQLTYPE scale (n2) is not the same as the column precision (m2)
                        // with AE: should not work
                        // without AE: should not work if n1 - n2 < m1 - m2 (Numeric value out of range error)
                        //             or n2 != 0 && $m1 == $m2 (Arithmetic overflow error)
                        if ($n1 != $m1 || $n2 != $m2) {
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
                                if ($n1 - $n2 < $m1 - $m2 || ($m1 == $m2 && $n2 == 0)) {
                                    if ($r !== false) {
                                        echo "Conversion from $sqltypeFull to $typeFull should not be supported\n";
                                    }
                                } else {
                                    if ($r === false) {
                                        echo "Conversion from $sqltypeFull to $typeFull should be supported\n";
                                    } else {
                                        $sql = "SELECT c_det, c_rand FROM $tbname";
                                        $stmt = sqlsrv_query($conn, $sql);
                                        $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                                        if (abs($row['c_det'] - $inputValues[0]) > $epsilon || abs($row['c_rand'] - $inputValues[1]) > $epsilon) {
                                            echo "Conversion from $sqltypeFull to $typeFull causes data corruption\n";
                                        }
                                    }
                                }
                            }
                        // check the case when the SQLSRV_SQLTYPE precision (n1) and scale (n2) are the same as the column precision (m1) and scale (m2)
                        // should work with AE or non AE
                        } else {
                            if ($r === false) {
                                echo "Conversion from $sqltypeFull to $typeFull should be supported\n";
                            } else {
                                $sql = "SELECT c_det, c_rand FROM $tbname";
                                $stmt = sqlsrv_query($conn, $sql);
                                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                                if (abs($row['c_det'] - $inputValues[0]) < $epsilon && abs($row['c_rand'] - $inputValues[1]) < $epsilon) {
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
            }
            dropTable($conn, $tbname);
        }
    }
}
sqlsrv_close($conn);
?>
--EXPECT--
Testing decimal(1, 0):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(1, 0) to decimal(1, 0) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(1, 0) to decimal(1, 0) is supported****

Testing decimal(1, 1):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(1, 1) to decimal(1, 1) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(1, 1) to decimal(1, 1) is supported****

Testing decimal(4, 0):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(4, 0) to decimal(4, 0) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(4, 0) to decimal(4, 0) is supported****

Testing decimal(4, 1):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(4, 1) to decimal(4, 1) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(4, 1) to decimal(4, 1) is supported****

Testing decimal(4, 4):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(4, 4) to decimal(4, 4) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(4, 4) to decimal(4, 4) is supported****

Testing decimal(16, 0):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(16, 0) to decimal(16, 0) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(16, 0) to decimal(16, 0) is supported****

Testing decimal(16, 1):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(16, 1) to decimal(16, 1) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(16, 1) to decimal(16, 1) is supported****

Testing decimal(16, 4):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(16, 4) to decimal(16, 4) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(16, 4) to decimal(16, 4) is supported****

Testing decimal(16, 16):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(16, 16) to decimal(16, 16) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(16, 16) to decimal(16, 16) is supported****

Testing decimal(38, 0):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 0) to decimal(38, 0) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 0) to decimal(38, 0) is supported****

Testing decimal(38, 1):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 1) to decimal(38, 1) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 1) to decimal(38, 1) is supported****

Testing decimal(38, 4):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 4) to decimal(38, 4) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 4) to decimal(38, 4) is supported****

Testing decimal(38, 16):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 16) to decimal(38, 16) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 16) to decimal(38, 16) is supported****

Testing decimal(38, 38):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 38) to decimal(38, 38) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 38) to decimal(38, 38) is supported****

Testing numeric(1, 0):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(1, 0) to numeric(1, 0) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(1, 0) to numeric(1, 0) is supported****

Testing numeric(1, 1):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(1, 1) to numeric(1, 1) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(1, 1) to numeric(1, 1) is supported****

Testing numeric(4, 0):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(4, 0) to numeric(4, 0) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(4, 0) to numeric(4, 0) is supported****

Testing numeric(4, 1):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(4, 1) to numeric(4, 1) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(4, 1) to numeric(4, 1) is supported****

Testing numeric(4, 4):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(4, 4) to numeric(4, 4) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(4, 4) to numeric(4, 4) is supported****

Testing numeric(16, 0):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(16, 0) to numeric(16, 0) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(16, 0) to numeric(16, 0) is supported****

Testing numeric(16, 1):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(16, 1) to numeric(16, 1) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(16, 1) to numeric(16, 1) is supported****

Testing numeric(16, 4):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(16, 4) to numeric(16, 4) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(16, 4) to numeric(16, 4) is supported****

Testing numeric(16, 16):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(16, 16) to numeric(16, 16) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(16, 16) to numeric(16, 16) is supported****

Testing numeric(38, 0):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 0) to numeric(38, 0) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 0) to numeric(38, 0) is supported****

Testing numeric(38, 1):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 1) to numeric(38, 1) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 1) to numeric(38, 1) is supported****

Testing numeric(38, 4):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 4) to numeric(38, 4) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 4) to numeric(38, 4) is supported****

Testing numeric(38, 16):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 16) to numeric(38, 16) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 16) to numeric(38, 16) is supported****

Testing numeric(38, 38):
****Conversion from SQLSRV_SQLTYPE_DECIMAL(38, 38) to numeric(38, 38) is supported****
****Conversion from SQLSRV_SQLTYPE_NUMERIC(38, 38) to numeric(38, 38) is supported****