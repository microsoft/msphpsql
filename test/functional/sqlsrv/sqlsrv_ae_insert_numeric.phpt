--TEST--
Test for inserting and retrieving encrypted data of numeric types
--DESCRIPTION--
Bind params using sqlsrv_prepare without any sql_type specified
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
require_once('AEData.inc');

$dataTypes = array( "bit", "tinyint", "smallint", "int", "bigint", "decimal(18,5)", "numeric(10,5)", "float", "real" );
$conn = AE\connect();

$count = count($dataTypes);
for ($i = 0; $i < $count; $i++) {
    $dataType = $dataTypes[$i];
    echo "\nTesting $dataType: \n";

    // create table
    $tbname = GetTempTableName("", false);
    $colMetaArr = array( new AE\ColumnMeta($dataType, "c_det"), new AE\ColumnMeta($dataType, "c_rand", null, false));
    AE\createTable($conn, $tbname, $colMetaArr);

    // insert a row
    $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
    $r;
    // convert input values to strings for decimals and numerics
    if ($i == 5 || $i == 6) {
        $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => (string) $inputValues[0], $colMetaArr[1]->colName => (string) $inputValues[1] ), $r);
    } else {
        $stmt = AE\insertRow($conn, $tbname, array( $colMetaArr[0]->colName => $inputValues[0], $colMetaArr[1]->colName => $inputValues[1] ), $r);
    }
    if ($r === false) {
        is_incompatible_types_error($dataType, "default type");
    } else {
        echo "****Encrypted default type is compatible with encrypted $dataType****\n";
        AE\fetchAll($conn, $tbname);
    }
    dropTable($conn, $tbname);
}
sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);
?>
--EXPECT--

Testing bit: 
****Encrypted default type is compatible with encrypted bit****
c_det: 1
c_rand: 0

Testing tinyint: 
****Encrypted default type is compatible with encrypted tinyint****
c_det: 0
c_rand: 255

Testing smallint: 
****Encrypted default type is compatible with encrypted smallint****
c_det: -32767
c_rand: 32767

Testing int: 
****Encrypted default type is compatible with encrypted int****
c_det: -2147483647
c_rand: 2147483647

Testing bigint: 
****Encrypted default type is compatible with encrypted bigint****
c_det: -922337203685479936
c_rand: 922337203685479936

Testing decimal(18,5): 
****Encrypted default type is compatible with encrypted decimal(18,5)****
c_det: -9223372036854.80000
c_rand: 9223372036854.80000

Testing numeric(10,5): 
****Encrypted default type is compatible with encrypted numeric(10,5)****
c_det: -21474.83647
c_rand: 21474.83647

Testing float: 
****Encrypted default type is compatible with encrypted float****
c_det: -9223372036.8548
c_rand: 9223372036.8548

Testing real: 
****Encrypted default type is compatible with encrypted real****
c_det: -2147.4829101562
c_rand: 2147.4829101562
