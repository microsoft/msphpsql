--TEST--
Test for inserting encrypted data of float types with different number of bits
--DESCRIPTION--
Test implicit conversions between different number of bits
With Always Encrypted, implicit conversion works if:
1. From input of SQLSRV_SQLTYPE_FLOAT to a float(m) column where m > 24
Note: with Always Encrypted, implicit conversion should work as long as the SQLSRV_SQLTYPE has a smaller number of bits than the one defined in the column. However, the SQLSRV driver does not let the user specify the number of bits in the SQLSRV_SQLTYPE_FLOAT constant and it is default to 53. Hence when user specifies SQLSRV_SQLTYPE_FLOAT when binding parameter during insertion, only insertion into a column of > 24 is allowed.
Without Always Encrypted, implicit conversion between different number of bits works.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataType = "float";
$bits = array(1, 12, 24, 36, 53);
$sqlType = "SQLSRV_SQLTYPE_FLOAT";
$inputValues = array(9223372036854775808.9223372036854775808, -9223372036854775808.9223372036854775808);
$epsilon = 100000;

$conn = AE\connect();
foreach($bits as $m) {
    $typeFull = "$dataType($m)";
    echo "\nTesting $typeFull:\n";
            
    // create table containing float(m) columns
    $tbname = "test_" . $dataType . $m;
    $colMetaArr = array(new AE\ColumnMeta($typeFull, "c_det"), new AE\ColumnMeta($typeFull, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);
    
    // insert by specifying SQLSRV_SQLTYPE_FLOAT
    $inputs = array(new AE\BindParamOption($inputValues[0], null, null, $sqlType), 
                    new AE\BindParamOption($inputValues[1], null, null, $sqlType));
    $r;
    $stmt = AE\insertRow($conn, $tbname, array("c_det" => $inputs[0], "c_rand" => $inputs[1]), $r, AE\INSERT_PREPARE_PARAMS);

    // check the case when the column number of bits is less than 25
    // with AE: should not work
    // without AE: should work
    if ($m < 25) {
        if (AE\isDataEncrypted()) {
            if ($r !== false) {
                echo "AE: Conversion from $sqlType to $typeFull should not be supported\n";
            } else {
                if (sqlsrv_errors()[0]['SQLSTATE'] != "22018") {
                    echo "AE: Conversion from $sqlType to $typeFull expects an operand type clash error, actual error is incorrect\n";
                    var_dump(sqlsrv_errors());
                } else {
                    echo "Test successfully done\n";
                }
            }
        } else {
            if ($r === false) {
                echo "Conversion from $sqlType to $typeFull should be supported\n";
            } else {
                $sql = "SELECT c_det, c_rand FROM $tbname";
                $stmt = sqlsrv_query($conn, $sql);
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if (abs($row['c_det'] - $inputValues[0]) > $epsilon || abs($row['c_rand'] - $inputValues[1]) > $epsilon) {
                    echo "Conversion from $sqlType to $typeFull causes data corruption\n";
                } else {
                    echo "Test successfully done\n";
                }
            }
        }
    // check the case when the column number of bits 25 or more
    // should work with AE or non AE
    } else {
        if ($r === false) {
            echo "Conversion from $sqlType to $typeFull should be supported\n";
        } else {
            $sql = "SELECT c_det, c_rand FROM $tbname";
            $stmt = sqlsrv_query($conn, $sql);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if (abs($row['c_det'] - $inputValues[0]) < $epsilon && abs($row['c_rand'] - $inputValues[1]) < $epsilon) {
                echo "****Conversion from $sqlType to $typeFull is supported****\n";
            } else {
                echo "Conversion from $sqlType to $typeFull causes data corruption\n";
            }
        }
    }
    // cleanup
    sqlsrv_free_stmt($stmt);
    dropTable($conn, $tbname);
}
sqlsrv_close($conn);

?>
--EXPECT--
Testing float(1):
Test successfully done

Testing float(12):
Test successfully done

Testing float(24):
Test successfully done

Testing float(36):
****Conversion from SQLSRV_SQLTYPE_FLOAT to float(36) is supported****

Testing float(53):
****Conversion from SQLSRV_SQLTYPE_FLOAT to float(53) is supported****