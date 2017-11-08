--TEST--
Test for inserting and retrieving encrypted data of money types
--DESCRIPTION--
No PDO::PARAM_ tpe specified when binding parameters
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");
$dataTypes = array("smallmoney", "money");
try {
    //set to ERRMODE_SILENT to compare actual error and expected unsupport money types in encrypted columns error
    $conn = connect('', array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        echo "\nTesting $dataType:\n";
        $success = true;

        // create table
        $tbname = getTableName();
        $colMetaArr = array(new columnMeta($dataType, "c_det", null, "deterministic", true),
                            new columnMeta($dataType, "c_rand", null, "randomized", true));
        createTable($conn, $tbname, $colMetaArr);

        // insert a row
        $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
        $r;
        $stmt = insertRow($conn, $tbname, array("c_det" => $inputValues[0], "c_rand" => $inputValues[1] ), null, $r);

        if (!isColEncrypted()) {
            if ($r === false) {
                echo "Default type should be compatible with $dataType.\n";
                $success = false;
            } else {
                $sql = "SELECT * FROM $tbname";
                $stmt = $conn->query($sql);
                $row = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($row["c_det"] != $inputValues[0] || $row["c_rand"] != $inputValues[1]) {
                    echo "Incorrect output retrieved for datatype $dataType.\n";
                    $success = false;
                }
            }
        } else {
            if ($r === false) {
                if ($stmt->errorInfo()[0] != "22018") {
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
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
--EXPECT--

Testing smallmoney:
Test successfully done.

Testing money:
Test successfully done.
