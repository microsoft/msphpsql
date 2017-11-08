--TEST--
Test for inserting and retrieving encrypted data of numeric types
--DESCRIPTION--
No PDO::PARAM_ tpe specified when binding parameters
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");
$dataTypes = array("bit", "tinyint", "smallint", "int", "bigint", "decimal(18,5)", "numeric(10,5)", "float", "real");
try {
    $conn = connect();
    foreach ($dataTypes as $dataType) {
        echo "\nTesting $dataType:\n";

        // create table
        $tbname = getTableName();
        $colMetaArr = array( new ColumnMeta($dataType, "c_det"), new ColumnMeta($dataType, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);

        // insert a row
        $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
        $r;
        $stmt = insertRow($conn, $tbname, array( "c_det" => $inputValues[0], "c_rand" => $inputValues[1] ), null, $r);
        if ($r === false) {
            isIncompatibleTypesError($stmt, $dataType, "default type");
        } else {
            echo "****Encrypted default type is compatible with encrypted $dataType****\n";
            fetchAll($conn, $tbname);
        }
        dropTable($conn, $tbname);
    }
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}
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
c_det: -9223372036.8547993
c_rand: 9223372036.8547993

Testing real:
****Encrypted default type is compatible with encrypted real****
c_det: -2147.4829
c_rand: 2147.4829
