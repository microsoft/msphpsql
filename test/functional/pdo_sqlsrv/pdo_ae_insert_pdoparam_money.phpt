--TEST--
Test for inserting and retrieving encrypted data of money types
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");
$dataTypes = array( "smallmoney", "money" );
try {
    //set to ERRMODE_SILENT to compare actual error and expected unsupport money types in encrypted columns error
    $conn = connect('', array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        echo "\nTesting $dataType:\n";
        $success = true;

        // create table
        $tbname = getTableName();
        $colMetaArr = array(new ColumnMeta($dataType, "c_det", null, "deterministic", true),
                            new ColumnMeta($dataType, "c_rand", null, "randomized", true));
        createTable($conn, $tbname, $colMetaArr);

        // test each PDO::PARAM_ type
        foreach ($pdoParamTypes as $pdoParamType) {
            // insert a row
            $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
            $r;
            $stmt = insertRow($conn, $tbname, array("c_det" => new BindParamOp(1, (string)$inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, (string)$inputValues[1], $pdoParamType)), "prepareBindParam", $r);


            if (!isColEncrypted()) {
                if ($r === false) {
                    echo "$pdoParamType should be compatible with $dataType.\n";
                    $success = false;
                } else {
                    $sql = "SELECT * FROM $tbname";
                    $stmt = $conn->query($sql);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (($row["c_det"] != $inputValues[0] || $row["c_rand"] != $inputValues[1]) && $pdoParamType != "PDO::PARAM_NULL") {
                        echo "Incorrect output retrieved for datatype $dataType.\n";
                        var_dump($inputValues);
                        var_dump($row);
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
            $conn->query("TRUNCATE TABLE $tbname");
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
