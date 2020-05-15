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

function fetchFields($conn, $tbname, $inputValues = null)
{
    try {
        $sql = "SELECT * FROM $tbname";
        $stmt = $conn->query($sql);

        if (is_null($inputValues)) {
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                foreach ($row as $key => $value) {
                    print("$key: $value\n");
                }
            }
        } else {
            while ($row = $stmt->fetch(PDO::FETCH_NUM)) {
                for ($i = 0; $i < 2; $i++) {
                    if (!compareFloats($inputValues[$i], $row[$i])) {
                        echo "Expected similar to $inputValues[$i] but got $row[$i]\n";
                    } else {
                        echo "Values matched\n";
                    }
                }
            }
        }
    } catch (PDOException $e) {
        var_dump($e->errorInfo);
    } catch (Exception $e) {
        var_dump($e->errorInfo);
        exit;
    }
}

// Note the size of a float is platform dependent, with a precision of roughly 14 digits
// http://php.net/manual/en/language.types.float.php
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
        } elseif ($dataType == 'float' || $dataType == 'real') {
            echo "-----Encrypted default type is compatible with encrypted $dataType-----\n";
            fetchFields($conn, $tbname, $inputValues);
        } else {
            echo "-----Encrypted default type is compatible with encrypted $dataType-----\n";
            fetchFields($conn, $tbname);
        }
        dropTable($conn, $tbname);
    }
    unset($stmt);
    unset($conn);
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
--EXPECTREGEX--

Testing bit:
-----Encrypted default type is compatible with encrypted bit-----
c_det: 1
c_rand: 0

Testing tinyint:
-----Encrypted default type is compatible with encrypted tinyint-----
c_det: 0
c_rand: 255

Testing smallint:
-----Encrypted default type is compatible with encrypted smallint-----
c_det: -32767
c_rand: 32767

Testing int:
-----Encrypted default type is compatible with encrypted int-----
c_det: -2147483647
c_rand: 2147483647

Testing bigint:
-----Encrypted default type is compatible with encrypted bigint-----
c_det: -922337203685479936
c_rand: 922337203685479936

Testing decimal\(18,5\):
-----Encrypted default type is compatible with encrypted decimal\(18,5\)-----
c_det: -9223372036854\.80000
c_rand: 9223372036854\.80000

Testing numeric\(10,5\):
-----Encrypted default type is compatible with encrypted numeric\(10,5\)-----
c_det: -21474\.83647
c_rand: 21474\.83647

Testing float:
-----Encrypted default type is compatible with encrypted float-----
Values matched
Values matched

Testing real:
-----Encrypted default type is compatible with encrypted real-----
Values matched
Values matched