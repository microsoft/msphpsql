--TEST--
Test for inserting empty strings and non-empty ones into binary types
--DESCRIPTION--
Test for inserting empty strings and non-empty ones into binary types
Related to GitHub PR 865 - verify that the same binary data can be reused rather
than flushed after the first use
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');

$conn = AE\connect();

$tableName = "sqlsrvEmptyBinary";
$size = 6;

$colMetaArr = array(new AE\ColumnMeta("binary($size)", "BinaryCol"),
                    new AE\ColumnMeta("varbinary($size)", "VarBinaryCol"),
                    new AE\ColumnMeta("varbinary(max)", "VarBinaryMaxCol"));
AE\createTable($conn, $tableName, $colMetaArr);

// Insert two rows, first empty strings and the second not empty
$inputValues = array('', 'ABC');

$inputs = array(new AE\BindParamOption($inputValues[0],
                                        null,
                                        "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)",
                                        "SQLSRV_SQLTYPE_BINARY($size)"),
                new AE\BindParamOption($inputValues[0],
                                        null,
                                        "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)",
                                        "SQLSRV_SQLTYPE_VARBINARY($size)"),
                new AE\BindParamOption($inputValues[0],
                                        null,
                                        "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)",
                                        "SQLSRV_SQLTYPE_VARBINARY('max')"));
$r;
$stmt = AE\insertRow($conn, $tableName, array("BinaryCol" => $inputs[0], "VarBinaryCol" => $inputs[1], "VarBinaryMaxCol" => $inputs[2]), $r, AE\INSERT_PREPARE_PARAMS);


$inputs = array(new AE\BindParamOption($inputValues[1],
                                        null,
                                        "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)",
                                        "SQLSRV_SQLTYPE_BINARY($size)"),
                new AE\BindParamOption($inputValues[1],
                                        null,
                                        "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)",
                                        "SQLSRV_SQLTYPE_VARBINARY($size)"),
                new AE\BindParamOption($inputValues[1],
                                        null,
                                        "SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)",
                                        "SQLSRV_SQLTYPE_VARBINARY('max')"));
$r;
$stmt = AE\insertRow($conn, $tableName, array("BinaryCol" => $inputs[0], "VarBinaryCol" => $inputs[1], "VarBinaryMaxCol" => $inputs[2]), $r, AE\INSERT_PREPARE_PARAMS);

// Verify the data by fetching and comparing against the inputs
$query = "SELECT * FROM $tableName";
$stmt = sqlsrv_query($conn, $query);
if (!$stmt) {
    fatalError("Failed to retrieve data from $tableName");
}

for ($i = 0; $i < 2; $i++) {
    $rowNum = $i + 1;
    $row = sqlsrv_fetch_array($stmt, SQLSRV_FETCH_NUMERIC);
    if (!$row) {
        fatalError("Failed in sqlsrv_fetch_array for row $rowNum");
    }

    for ($j = 0; $j < 3; $j++) {
        $str = $row[$j];
        $len = strlen($str);
        $failed = false;

        if ($j == 0) {
            // binary fields have fixed size, unlike varbinary ones
            if ($len !== $size || trim($str) !== $inputValues[$i]) {
                $failed = true;
            }
        } else {
            $inputLen = strlen($inputValues[$i]);
            if ($len !== $inputLen || $str !== $inputValues[$i]) {
                $failed = true;
            }
        }

        if ($failed) {
            $colNum = $j + 1;
            echo "Unexpected value returned from row $rowNum and column $colNum: \n";
            var_dump($str);
        }
    }
}

dropTable($conn, $tableName);

sqlsrv_free_stmt($stmt);
sqlsrv_close($conn);

echo "Done\n";

?>
--EXPECT--
Done
