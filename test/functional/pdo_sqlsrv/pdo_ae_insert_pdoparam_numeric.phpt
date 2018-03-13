--TEST--
Test for inserting encrypted data into numeric types columns
--DESCRIPTION--
Test conversions between different numeric types
With Always Encrypted, implicit conversion works if:
1. From input of PDO::PARAM_BOOL to a real column
2. From input of PDO::PARAM_INT to any numeric column
3. From input of PDO::PARAM_STR to any numeric column
4. From input of PDO::PARAM_LOB to any numeric column
Without Always Encrypted, all of the above work except for input of PDO::PARAM_STR to a bigint column in a x86 platform
PDO::PARAM_STR does not work for bigint in a x86 platform because the maximum value of an int is about 2147483647
Whereas in a x64 platform, the maximum value is about 9E18
In a x86 platform, when an integer is > 2147483647, PHP implicitly changees it to a float, represented by scientific notation
When inserting a scientific notation form numeric string, SQL Server returns a converting data type nvarchar to bigint error
Works for with AE because the sqltype used for binding parameter is determined by SQLDescribeParam,
unlike without AE, the sqltype is predicted to be nvarchar or varchar when the input is a string
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");

$dataTypes = array("bit", "tinyint", "smallint", "int", "bigint", "real");
$epsilon = 1;

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        echo "\nTesting $dataType:\n";

        // create table containing bit, tinyint, smallint, int, bigint, or real columns
        $tbname = "test_" . $dataType;
        $colMetaArr = array(new ColumnMeta($dataType, "c_det"), new ColumnMeta($dataType, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);

        // insert by specifying PDO::PARAM_ types
        foreach ($pdoParamTypes as $pdoParamType) {
            $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
            $r;
            $stmt = insertRow($conn, $tbname, array( "c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType), "c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
            
            // check the case when inserting as PDO::PARAM_NULL
            // with or without AE: NULL is inserted
            if ($pdoParamType == "PDO::PARAM_NULL") {
                if ($r === false) {
                    echo "Conversion from $pdoParamType to $dataType should be supported\n";
                } else {
                    $sql = "SELECT c_det, c_rand FROM $tbname";
                    $stmt = $conn->query($sql);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if (!is_null($row['c_det']) || !is_null($row['c_rand'])) {
                        echo "Conversion from $pdoParamType to $dataType should insert NULL\n";
                    }
                }
            // check the case when inserting as PDO::PARAM_BOOL
            // with or without AE: 1 or 0 should be inserted when inserting into an integer column
            //                     double is inserted when inserting into a real column
            } else if ($pdoParamType == "PDO::PARAM_BOOL") {
                if ($r === false) {
                    echo "Conversion from $pdoParamType to $dataType should be supported\n";
                } else {
                    $sql = "SELECT c_det, c_rand FROM $tbname";
                    $stmt = $conn->query($sql);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($dataType == "real") {
                        if (abs($row['c_det'] - $inputValues[0]) < $epsilon && abs($row['c_rand'] - $inputValues[1]) < $epsilon) {
                            echo "****Conversion from $pdoParamType to $dataType is supported****\n";
                        } else {
                            echo "Conversion from $pdoParamType to $dataType causes data corruption\n";
                        }
                    } else {
                        if ($row['c_det'] != ($inputValues[0] != 0) && $row['c_rand'] != ($inputValues[1] != 0)) {
                            echo "Conversion from $pdoParamType to $dataType insert a boolean\n";
                        }
                    }
                }
            // check the case when inserting as PDO::PARAM_STR into a bigint column
            // with AE: should work
            // without AE: should not work on a x86 platform
            } else if ($dataType == "bigint" && $pdoParamType == "PDO::PARAM_STR") {
                if (!isAEConnected() && PHP_INT_SIZE == 4) {
                    if ($r !== false) {
                        echo "Conversion from $pdoParamType to $dataType should not be supported\n";
                    }
                } else {
                    if ($r === false) {
                        echo "Conversion from $pdoParamType to $dataType should be supported\n";
                    } else {
                        $sql = "SELECT c_det, c_rand FROM $tbname";
                        $stmt = $conn->query($sql);
                        $row = $stmt->fetch(PDO::FETCH_ASSOC);
                        if ($row['c_det'] != $inputValues[0] && $row['c_rand'] != $inputValues[1]) {
                            echo "Conversion from $pdoParamType to $dataType causes data corruption\n";
                        }
                    }
                }
            // check the case when inserting as PDO::PARAM_INT, PDO::PARAM_STR or PDO::PARAM_LOB
            // with or without AE: should work
            } else {
                if ($r === false) {
                    echo "Conversion from $pdoParamType to $dataType should be supported\n";
                } else {
                    $sql = "SELECT c_det, c_rand FROM $tbname";
                    $stmt = $conn->query($sql);
                    $row = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($dataType == "real") {
                        if (abs($row['c_det'] - $inputValues[0]) < $epsilon && abs($row['c_rand'] - $inputValues[1]) < $epsilon) {
                            echo "****Conversion from $pdoParamType to $dataType is supported****\n";
                        } else {
                            echo "Conversion from $pdoParamType to $dataType causes data corruption\n";
                        }
                    } else {
                        if ($row['c_det'] == $inputValues[0] && $row['c_rand'] == $inputValues[1]) {
                            echo "****Conversion from $pdoParamType to $dataType is supported****\n";
                        } else {
                            echo "Conversion from $pdoParamType to $dataType causes data corruption\n";
                        }
                    }
                }
            }
            $conn->query("TRUNCATE TABLE $tbname");
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
****Conversion from PDO::PARAM_INT to bit is supported****
****Conversion from PDO::PARAM_STR to bit is supported****
****Conversion from PDO::PARAM_LOB to bit is supported****

Testing tinyint:
****Conversion from PDO::PARAM_INT to tinyint is supported****
****Conversion from PDO::PARAM_STR to tinyint is supported****
****Conversion from PDO::PARAM_LOB to tinyint is supported****

Testing smallint:
****Conversion from PDO::PARAM_INT to smallint is supported****
****Conversion from PDO::PARAM_STR to smallint is supported****
****Conversion from PDO::PARAM_LOB to smallint is supported****

Testing int:
****Conversion from PDO::PARAM_INT to int is supported****
****Conversion from PDO::PARAM_STR to int is supported****
****Conversion from PDO::PARAM_LOB to int is supported****

Testing bigint:
****Conversion from PDO::PARAM_INT to bigint is supported****
****Conversion from PDO::PARAM_LOB to bigint is supported****

Testing real:
****Conversion from PDO::PARAM_BOOL to real is supported****
****Conversion from PDO::PARAM_INT to real is supported****
****Conversion from PDO::PARAM_STR to real is supported****
****Conversion from PDO::PARAM_LOB to real is supported****