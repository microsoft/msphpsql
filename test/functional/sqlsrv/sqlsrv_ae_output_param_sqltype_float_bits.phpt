--TEST--
Test for retrieving encrypted data of float types with different number of bits as output parameters
--DESCRIPTION--
Test implicit conversions between different number of bits
With Always Encrypted, implicit conversion works if:
1. From a float(m) column to a SQLSRV_SQLTYPE_FLOAT output parameter where m > 24
Without Always Encrypted, implicit conversion between different number of bits works
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataType = "float";
$bits = array(1, 12, 24, 36, 53);
$sqlType = "SQLSRV_SQLTYPE_FLOAT";
$inputValues = array(9223372036854775808.9223372036854775808, -9223372036854775808.9223372036854775808);
$directions = array("SQLSRV_PARAM_OUT", "SQLSRV_PARAM_INOUT");
$epsilon = 100000;

$conn = AE\connect();
foreach ($bits as $m) {
    $typeFull = "$dataType($m)";
    echo "\nTesting $typeFull:\n";
    
    // create and populate table containing float(m) columns
    $tbname = "test_" . $dataType . $m;
    $colMetaArr = array(new AE\ColumnMeta($typeFull, "c_det"), new AE\ColumnMeta($typeFull, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);
    $stmt = AE\insertRow($conn, $tbname, array($colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1]));
    
    // create a stored procedure and sql string for calling the stored procedure
    $spname = 'selectAllColumns';
    createProc($conn, $spname, "@c_det $typeFull OUTPUT, @c_rand $typeFull OUTPUT", "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname");
    $sql = AE\getCallProcSqlPlaceholders($spname, 2);

    // retrieve by specifying SQLSRV_SQLTYPE_FLOAT as SQLSRV_PARAM_OUT or SQLSRV_PARAM_INOUT
    foreach ($directions as $dir) {
        echo "Testing as $dir:\n";
        
        $c_detOut = 0.0;
        $c_randOut = 0.0;
        $stmt = sqlsrv_prepare($conn, $sql, array(array(&$c_detOut, constant($dir), null, constant($sqlType)), array(&$c_randOut, constant($dir), null, constant($sqlType))));
        $r = sqlsrv_execute($stmt);
        
        // check the case when the column number of bits is less than 25
        // when the number of bits is between 1 and 24, it corresponds to a storage size of 4 bytes
        // when the number of bits is between 25 and 53, it corresponds to a storage size of 8 bytes
        // with AE: should not work because SQLSRV_SQLTYPE_FLOAT maps to float(53) and conversion from a larger float to a smaller float is not supported
        // without AE: should work
        if ($m < 25) {
            if (AE\isDataEncrypted()) {
                if ($r !== false) {
                    echo "AE: Conversion between $typeFull to output $sqlType should not be supported\n";
                } else {
                    if (sqlsrv_errors()[0]['SQLSTATE'] != "22018") {
                        echo "AE: Conversion from $typeFull to output $sqlType expects an operand type clash error, actual error is incorrect\n";
                        var_dump(sqlsrv_errors());
                    } else {
                        echo "Test successfully done\n";
                    }
                }
            } else {
                if ($r === false) {
                    echo "Conversion from $typeFull to output $sqlType should be supported\n";
                } else {
                    if (abs($c_detOut - $inputValues[0]) > $epsilon || abs($c_randOut - $inputValues[1]) > $epsilon) {
                        echo "Conversion from $typeFull to output $sqlType causes data corruption\n";
                    } else {
                        echo "Test successfully done\n";
                    }
                }
            }
        // check the case when the column number of bits 25 or more
        // should work with or without AE
        } else {
            if ($r === false) {
                echo "Conversion from $typeFull to output $sqlType should be supported\n";
            } else {
                if (abs($c_detOut - $inputValues[0]) < $epsilon && abs($c_randOut - $inputValues[1]) < $epsilon) {
                    echo "****Conversion from $typeFull to output $sqlType is supported****\n";
                } else {
                    echo "Conversion from $typeFull to output $sqlType causes data corruption\n";
                }
            }
        }
        // cleanup
        sqlsrv_free_stmt($stmt);
    }
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
}
sqlsrv_close($conn);
                
?>
--EXPECT--
Testing float(1):
Testing as SQLSRV_PARAM_OUT:
Test successfully done
Testing as SQLSRV_PARAM_INOUT:
Test successfully done

Testing float(12):
Testing as SQLSRV_PARAM_OUT:
Test successfully done
Testing as SQLSRV_PARAM_INOUT:
Test successfully done

Testing float(24):
Testing as SQLSRV_PARAM_OUT:
Test successfully done
Testing as SQLSRV_PARAM_INOUT:
Test successfully done

Testing float(36):
Testing as SQLSRV_PARAM_OUT:
****Conversion from float(36) to output SQLSRV_SQLTYPE_FLOAT is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from float(36) to output SQLSRV_SQLTYPE_FLOAT is supported****

Testing float(53):
Testing as SQLSRV_PARAM_OUT:
****Conversion from float(53) to output SQLSRV_SQLTYPE_FLOAT is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from float(53) to output SQLSRV_SQLTYPE_FLOAT is supported****