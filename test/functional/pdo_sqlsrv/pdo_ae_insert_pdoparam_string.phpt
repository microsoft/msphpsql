--TEST--
Test for inserting and retrieving encrypted data of string types
--DESCRIPTION--
Use PDOstatement::bindParam with all PDO::PARAM_ types
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("AEData.inc");
$dataTypes = array("char(5)", "varchar(max)", "nchar(5)", "nvarchar(max)");
try {
    $conn = connect('', array(), PDO::ERRMODE_SILENT);
    foreach ($dataTypes as $dataType) {
        echo "\nTesting $dataType:\n";

        // create table
        $tbname = getTableName();
        $colMetaArr = array(new ColumnMeta($dataType, "c_det"), new ColumnMeta($dataType, "c_rand", null, "randomized"));
        createTable($conn, $tbname, $colMetaArr);

        // prepare statement for inserting into table
        foreach ($pdoParamTypes as $pdoParamType) {
            // insert a row
            $inputValues = array_slice(${explode("(", $dataType)[0] . "_params"}, 1, 2);
            $r;
            $stmt = insertRow($conn, $tbname, array( "c_det" => new BindParamOp(1, $inputValues[0], $pdoParamType),"c_rand" => new BindParamOp(2, $inputValues[1], $pdoParamType)), "prepareBindParam", $r);
            if ($r === false) {
                isIncompatibleTypesError($stmt, $dataType, $pdoParamType);
            } else {
                echo "****PDO param type $pdoParamType is compatible with encrypted $dataType****\n";
                fetchAll($conn, $tbname);
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

Testing char(5):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted char(5)****
c_det: -leng
c_rand: th, n
****PDO param type PDO::PARAM_NULL is compatible with encrypted char(5)****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted char(5)****
c_det: -leng
c_rand: th, n
****PDO param type PDO::PARAM_STR is compatible with encrypted char(5)****
c_det: -leng
c_rand: th, n
****PDO param type PDO::PARAM_LOB is compatible with encrypted char(5)****
c_det: -leng
c_rand: th, n

Testing varchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted varchar(max)****
c_det: Use varchar(max) when the sizes of the column data entries vary considerably, and the size might exceed 8,000 bytes.
c_rand: Each non-null varchar(max) or nvarchar(max) column requires 24 bytes of additional fixed allocation which counts against the 8,060 byte row limit during a sort operation.
****PDO param type PDO::PARAM_NULL is compatible with encrypted varchar(max)****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted varchar(max)****
c_det: Use varchar(max) when the sizes of the column data entries vary considerably, and the size might exceed 8,000 bytes.
c_rand: Each non-null varchar(max) or nvarchar(max) column requires 24 bytes of additional fixed allocation which counts against the 8,060 byte row limit during a sort operation.
****PDO param type PDO::PARAM_STR is compatible with encrypted varchar(max)****
c_det: Use varchar(max) when the sizes of the column data entries vary considerably, and the size might exceed 8,000 bytes.
c_rand: Each non-null varchar(max) or nvarchar(max) column requires 24 bytes of additional fixed allocation which counts against the 8,060 byte row limit during a sort operation.
****PDO param type PDO::PARAM_LOB is compatible with encrypted varchar(max)****
c_det: Use varchar(max) when the sizes of the column data entries vary considerably, and the size might exceed 8,000 bytes.
c_rand: Each non-null varchar(max) or nvarchar(max) column requires 24 bytes of additional fixed allocation which counts against the 8,060 byte row limit during a sort operation.

Testing nchar(5):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted nchar(5)****
c_det: -leng
c_rand: th Un
****PDO param type PDO::PARAM_NULL is compatible with encrypted nchar(5)****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted nchar(5)****
c_det: -leng
c_rand: th Un
****PDO param type PDO::PARAM_STR is compatible with encrypted nchar(5)****
c_det: -leng
c_rand: th Un
****PDO param type PDO::PARAM_LOB is compatible with encrypted nchar(5)****
c_det: -leng
c_rand: th Un

Testing nvarchar(max):
****PDO param type PDO::PARAM_BOOL is compatible with encrypted nvarchar(max)****
c_det: When prefixing a string constant with the letter N, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type (4,000).
c_rand: Otherwise, the implicit conversion will result in a Unicode large-value (max).
****PDO param type PDO::PARAM_NULL is compatible with encrypted nvarchar(max)****
c_det: 
c_rand: 
****PDO param type PDO::PARAM_INT is compatible with encrypted nvarchar(max)****
c_det: When prefixing a string constant with the letter N, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type (4,000).
c_rand: Otherwise, the implicit conversion will result in a Unicode large-value (max).
****PDO param type PDO::PARAM_STR is compatible with encrypted nvarchar(max)****
c_det: When prefixing a string constant with the letter N, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type (4,000).
c_rand: Otherwise, the implicit conversion will result in a Unicode large-value (max).
****PDO param type PDO::PARAM_LOB is compatible with encrypted nvarchar(max)****
c_det: When prefixing a string constant with the letter N, the implicit conversion will result in a Unicode string if the constant to convert does not exceed the max length for a Unicode string data type (4,000).
c_rand: Otherwise, the implicit conversion will result in a Unicode large-value (max).
