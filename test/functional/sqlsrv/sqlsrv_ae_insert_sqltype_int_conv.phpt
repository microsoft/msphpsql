--TEST--
Test for inserting encrypted data of int types
--DESCRIPTION--
Test implicit conversions between different integer types
With Always Encrypted, implicit conversion works if:
1. From input SQLSRV_SQLTYPE_BIT to a bit column
2. From input SQLSRV_SQLTYPE_BIT to a tinyint column
3. From input SQLSRV_SQLTYPE_BIT to a smallint column
4. From input SQLSRV_SQLTYPE_BIT to an int column
5. From input SQLSRV_SQLTYPE_BIT to a bigint column
6. From input SQLSRV_SQLTYPE_TINYINT to a tinyint column
7. From input SQLSRV_SQLTYPE_TINYINT to a smallint column
8. From input SQLSRV_SQLTYPE_TINYINT to an int column
9. From input SQLSRV_SQLTYPE_TINYINT to a bigint column
10. From input SQLSRV_SQLTYPE_SMALLINT to a smallint column
11. From input SQLSRV_SQLTYPE_SMALLINT to an int column
12. From input SQLSRV_SQLTYPE_SMALLINT to a bigint column
13. From input SQLSRV_SQLTYPE_INT to an int column
14. From input SQLSRV_SQLTYPE_INT to a bigint column
15. From input SQLSRV_SQLTYPE_BIGINT to a bigint column
Without AlwaysEncrypted, implicit conversion between different integer types works
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("bit", "tinyint", "smallint", "int", "bigint");
$sqlTypes = array("SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_BIGINT");
// only 1 and 0 inputs are tested as they are the only values that fit into all integer types
// this test is for testing different integer conversions, if the input value does not fit into a datatype,
// the conversion would fail not because the conversion is not supported, but because of other errors such as truncation
$inputValues = array(1, 0);

// this is a list of implicit datatype conversion that AE supports
$aeConvList = array("bit" => array("SQLSRV_SQLTYPE_BIT"),
                    "tinyint" => array("SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TINYINT"),
                    "smallint" => array("SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_SMALLINT"),
                    "int" => array("SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_INT"),
                    "bigint" => array("SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_BIGINT"));
                    
$conn = AE\connect();
foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType:\n";
    
    // create table containing bit, tinyint, smallint, int, or bigint columns
    $tbname = "test_" . $dataType;
    $colMetaArr = array( new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);
    
    // insert by specifying different SQLSRV_SQLTYPE integer constants
    // with AE, should only be successful if the SQLSRV_SQLTYPE is smaller in size than the column datatype
    foreach($sqlTypes as $sqlType) {
        $inputs = array(new AE\BindParamOption($inputValues[0], null, null, $sqlType), new AE\BindParamOption($inputValues[1], null, null, $sqlType));
        $r;
        $stmt = AE\insertRow($conn, $tbname, array($colMetaArr[0]->colName => $inputs[0], $colMetaArr[1]->colName => $inputs[1]), $r, AE\INSERT_PREPARE_PARAMS);
        
        // check the case if the type conversion is not listed in $aeConvList
        if (!in_array($sqlType, $aeConvList["$dataType"])) {
            if (AE\isDataEncrypted()) {
                if ($r !== false) {
                    echo "AE: Conversion from $sqlType to $dataType should not be supported\n";
                } else {
                    if (sqlsrv_errors()[0]['SQLSTATE'] != "22018") {
                        echo "AE: Conversion from $sqlType to $dataType expects an operand type clash error, actual error is incorrect\n";
                        var_dump(sqlsrv_errors());
                    }
                }
            } else {
                if ($r === false) {
                    echo "Conversion from $sqlType to $dataType should be supported\n";
                } else {
                    $sql = "SELECT c_det, c_rand FROM $tbname";
                    $stmt = sqlsrv_query($conn, $sql);
                    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                    if ($row['c_det'] != $inputValues[0] || $row['c_rand'] != $inputValues[1]) {
                        echo "Conversion from $sqlType to $dataType causes data corruption\n";
                    }
                }
            }
        } else {
            if ($r === false) {
                echo "Conversion from $sqlType to $dataType should be supported\n";
            } else {
                $sql = "SELECT c_det, c_rand FROM $tbname";
                $stmt = sqlsrv_query($conn, $sql);
                $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
                if ($row['c_det'] == $inputValues[0] && $row['c_rand'] == $inputValues[1]) {
                    echo "****Conversion from $sqlType to $dataType is supported****\n";
                } else {
                    echo "Conversion from $sqlType to $dataType causes data corruption\n";
                }
            }
        }
        // cleanup
        sqlsrv_free_stmt($stmt);
        sqlsrv_query($conn, "TRUNCATE TABLE $tbname");
    }
    dropTable($conn, $tbname);
}
sqlsrv_close($conn);
?>
--EXPECT--
Testing bit:
****Conversion from SQLSRV_SQLTYPE_BIT to bit is supported****

Testing tinyint:
****Conversion from SQLSRV_SQLTYPE_BIT to tinyint is supported****
****Conversion from SQLSRV_SQLTYPE_TINYINT to tinyint is supported****

Testing smallint:
****Conversion from SQLSRV_SQLTYPE_BIT to smallint is supported****
****Conversion from SQLSRV_SQLTYPE_TINYINT to smallint is supported****
****Conversion from SQLSRV_SQLTYPE_SMALLINT to smallint is supported****

Testing int:
****Conversion from SQLSRV_SQLTYPE_BIT to int is supported****
****Conversion from SQLSRV_SQLTYPE_TINYINT to int is supported****
****Conversion from SQLSRV_SQLTYPE_SMALLINT to int is supported****
****Conversion from SQLSRV_SQLTYPE_INT to int is supported****

Testing bigint:
****Conversion from SQLSRV_SQLTYPE_BIT to bigint is supported****
****Conversion from SQLSRV_SQLTYPE_TINYINT to bigint is supported****
****Conversion from SQLSRV_SQLTYPE_SMALLINT to bigint is supported****
****Conversion from SQLSRV_SQLTYPE_INT to bigint is supported****
****Conversion from SQLSRV_SQLTYPE_BIGINT to bigint is supported****