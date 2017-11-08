--TEST--
Test for inserting and retrieving encrypted data of datetime types
--DESCRIPTION--
No PDO::PARAM_ type specified when binding parameters
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array( "date", "datetime", "datetime2", "smalldatetime", "time", "datetimeoffset" );

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
        $stmt = insertRow($conn, $tbname, array("c_det" => $inputValues[0], "c_rand" => $inputValues[1] ), null, $r);
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

Testing date:
****Encrypted default type is compatible with encrypted date****
c_det: 0001-01-01
c_rand: 9999-12-31

Testing datetime:
****Encrypted default type is compatible with encrypted datetime****
c_det: 1753-01-01 00:00:00.000
c_rand: 9999-12-31 23:59:59.997

Testing datetime2:
****Encrypted default type is compatible with encrypted datetime2****
c_det: 0001-01-01 00:00:00.0000000
c_rand: 9999-12-31 23:59:59.9999999

Testing smalldatetime:
****Encrypted default type is compatible with encrypted smalldatetime****
c_det: 1900-01-01 00:00:00
c_rand: 2079-06-05 23:59:00

Testing time:
****Encrypted default type is compatible with encrypted time****
c_det: 00:00:00.0000000
c_rand: 23:59:59.9999999

Testing datetimeoffset:
****Encrypted default type is compatible with encrypted datetimeoffset****
c_det: 0001-01-01 00:00:00.0000000 -14:00
c_rand: 9999-12-31 23:59:59.9999999 +14:00
