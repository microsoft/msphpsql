--TEST--
Testing statement option with integer and invalid string key
--ENV--
PHPT_EXEC=true
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php

include 'MsCommon.inc';
$conn = AE\connect();
$tableName = 'InvalidKeyTest';
AE\createTestTable($conn, $tableName);

$query = "SELECT * FROM [$tableName]";
//integer keys are invalid
$option = array( 5 => 1 );
$stmt = sqlsrv_query($conn, $query, null, $option);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

$stmt = null;
$stmt = sqlsrv_prepare($conn, $query, null, $option);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

$stmt = null;
//a key that is not supported by the driver
$option = array( "invalid_string_key" => 1 );
$stmt = sqlsrv_query($conn, $query, null, $option);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

$stmt = null;
$stmt = sqlsrv_prepare($conn, $query, null, $option);
if (!$stmt) {
    print_r(sqlsrv_errors());
}

dropTable($conn, $tableName);
sqlsrv_close($conn);

?>
--EXPECT--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -32
            [code] => -32
            [2] => Option 5 is invalid.
            [message] => Option 5 is invalid.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -32
            [code] => -32
            [2] => Option 5 is invalid.
            [message] => Option 5 is invalid.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -32
            [code] => -32
            [2] => Option invalid_string_key is invalid.
            [message] => Option invalid_string_key is invalid.
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -32
            [code] => -32
            [2] => Option invalid_string_key is invalid.
            [message] => Option invalid_string_key is invalid.
        )

)
