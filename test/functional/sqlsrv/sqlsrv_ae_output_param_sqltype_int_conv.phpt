--TEST--
Test for retrieving encrypted data of int types as output parameters
--DESCRIPTION--
Test implicit conversions between different integer types
With Always Encrypted, implicit conversion works if the column type and the SQLSRV_SQLTYPE are the same
Without AlwaysEncrypted, implicit conversion between different integer types works
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$dataTypes = array("bit", "tinyint", "smallint", "int", "bigint");
$sqlTypes = array("SQLSRV_SQLTYPE_BIT", "SQLSRV_SQLTYPE_TINYINT", "SQLSRV_SQLTYPE_SMALLINT", "SQLSRV_SQLTYPE_INT", "SQLSRV_SQLTYPE_BIGINT");
$directions = array("SQLSRV_PARAM_OUT", "SQLSRV_PARAM_INOUT");
$inputValues = array(1, 0);

$conn = AE\connect();
foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType:\n";
    
    // create and populate table containing bit, tinyint, smallint, int, or bigint columns
    $tbname = "test_" . $dataType;
    $colMetaArr = array( new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);
    $stmt = AE\insertRow($conn, $tbname, array($colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1]));
    
    // create a stored procedure and sql string for calling the stored procedure
    $spname = 'selectAllColumns';
    createProc($conn, $spname, "@c_det $dataType OUTPUT, @c_rand $dataType OUTPUT", "SELECT @c_det = c_det, @c_rand = c_rand FROM $tbname");
    $sql = AE\getCallProcSqlPlaceholders($spname, 2);
    
    // retrieve by specifying different SQLSRV_SQLTYPE ingeter constants as SQLSRV_PARAM_OUT or SQLSRV_PARAM_INOUT
    foreach ($directions as $dir) {
        echo "Testing as $dir:\n";
        foreach ($sqlTypes as $sqlType) {
            $c_detOut = 0;
            $c_randOut = 0;
            $stmt = sqlsrv_prepare($conn, $sql, array(array(&$c_detOut, constant($dir), null, constant($sqlType)), array(&$c_randOut, constant($dir), null, constant($sqlType))));
            $r = sqlsrv_execute($stmt);
            
            // check the case if the column type is not the same as the SQLSRV_SQLTYPE
            if ($sqlType != "SQLSRV_SQLTYPE_" . strtoupper($dataType)) {
                if (AE\isDataEncrypted()) {
                    if ($r !== false) {
                        echo "AE: Conversion from $dataType to output $sqlType should not be supported\n";
                    } else {
                        if (sqlsrv_errors()[0]['SQLSTATE'] != "22018") {
                            echo "AE: Conversion from $dataType to output $sqlType expects an operand type clash error, actual error is incorrect\n";
                            var_dump(sqlsrv_errors());
                        }
                    }
                } else {
                    if ($r === false) {
                        echo "Conversion from $dataType to output $sqlType should be supported\n";
                    } else {
                        if ($c_detOut != $inputValues[0] || $c_randOut != $inputValues[1]) {
                            echo "Conversion from $dataType to output $sqlType causes data corruption\n";
                        }
                    }
                }
            // check the case if the column type is the same as the SQLSRV_SQLTYPE
            } else {
                if ($r === false) {
                    echo "Conversion from $dataType to output $sqlType should be supported\n";
                } else {
                    if ($c_detOut == $inputValues[0] && $c_randOut == $inputValues[1]) {
                        echo "****Conversion from $dataType to output $sqlType is supported****\n";
                    } else {
                        echo "Conversion from $dataType to output $sqlType causes data corruption\n";
                    }
                }
            }
            // cleanup
            sqlsrv_free_stmt($stmt);
        }
    }
    dropProc($conn, $spname);
    dropTable($conn, $tbname);
}
sqlsrv_close($conn);

?>
--EXPECT--
Testing bit:
Testing as SQLSRV_PARAM_OUT:
****Conversion from bit to output SQLSRV_SQLTYPE_BIT is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from bit to output SQLSRV_SQLTYPE_BIT is supported****

Testing tinyint:
Testing as SQLSRV_PARAM_OUT:
****Conversion from tinyint to output SQLSRV_SQLTYPE_TINYINT is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from tinyint to output SQLSRV_SQLTYPE_TINYINT is supported****

Testing smallint:
Testing as SQLSRV_PARAM_OUT:
****Conversion from smallint to output SQLSRV_SQLTYPE_SMALLINT is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from smallint to output SQLSRV_SQLTYPE_SMALLINT is supported****

Testing int:
Testing as SQLSRV_PARAM_OUT:
****Conversion from int to output SQLSRV_SQLTYPE_INT is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from int to output SQLSRV_SQLTYPE_INT is supported****

Testing bigint:
Testing as SQLSRV_PARAM_OUT:
****Conversion from bigint to output SQLSRV_SQLTYPE_BIGINT is supported****
Testing as SQLSRV_PARAM_INOUT:
****Conversion from bigint to output SQLSRV_SQLTYPE_BIGINT is supported****