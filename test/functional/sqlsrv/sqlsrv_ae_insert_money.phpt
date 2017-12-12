--TEST--
Test for inserting and retrieving encrypted data of money types
--DESCRIPTION--
Bind params using sqlsrv_prepare without any sql_type specified
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('AEData.inc');

use AE\ColumnMeta;

$dataTypes = array( "smallmoney", "money" );
$conn = AE\connect();

foreach ($dataTypes as $dataType) {
    echo "\nTesting $dataType: \n";
    $success = true;

    // create table
    $tbname = GetTempTableName("", false);
    $colMetaArr = array( new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);

    // insert a row
    $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
    $r;
    $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r);

    if (!AE\isDataEncrypted()) {
        if ($r === false) {
            echo "Default type should be compatible with $dataType.\n";
            $success = false;
        } else {
            $sql = "SELECT * FROM $tbname";
            $stmt = sqlsrv_query($conn, $sql);
            $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_ASSOC);
            if ($row["c_det"] != $inputValues[0] || $row["c_rand"] != $inputValues[1]) {
                echo "Incorrect output retrieved for datatype $dataType and sqlType $sqlType.\n";
                $success = false;
            }
        }
    } else {
        if ($r === false) {
            if (sqlsrv_errors()[0]['SQLSTATE'] != 22018) {
                echo "Incorrect error returned.\n";
                $success = false;
            }
        } else {
            echo "$dataType is not compatible with any type.\n";
            $success = false;
        }
    }
    if ($success) {
        echo "Test successfully done.\n";
    }
    dropTable($conn, $tbname);
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECT--

Testing smallmoney: 
Test successfully done.

Testing money: 
Test successfully done.
