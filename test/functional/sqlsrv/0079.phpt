--TEST--
Invalid UTF-16 coming from the server
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php

sqlsrv_configure('WarningsReturnAsErrors', 0);
sqlsrv_configure('LogSeverity', SQLSRV_LOG_SEVERITY_ALL);

// For testing in Azure, can not switch databases
require_once('MsCommon.inc');
$conn = connect();

if (!$conn) {
    var_dump(sqlsrv_errors());
}

$s = sqlsrv_query($conn, "IF OBJECT_ID('utf16invalid', 'U') IS NOT NULL DROP TABLE utf16invalid");
$s = sqlsrv_query($conn, "CREATE TABLE utf16invalid (id int identity, c1 nvarchar(100))");
if ($s === false) {
    die(print_r(sqlsrv_errors(), true));
}

// 0xdc00,0xdbff is an invalid surrogate pair
$invalid_utf16 = pack("H*", '410042004300440000DCFFDB45004600');

$s = sqlsrv_query(
    $conn,
    "INSERT INTO utf16invalid (c1) VALUES (?)",
                   array(
                       array( &$invalid_utf16, SQLSRV_PARAM_IN, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_BINARY)) )
);
if ($s === false) {
    die(print_r(sqlsrv_errors(), true));
}

$s = sqlsrv_query($conn, 'SELECT * FROM utf16invalid');
if ($s === false) {
    die(print_r(sqlsrv_errors(), true));
}

sqlsrv_fetch($s);

$utf8 = sqlsrv_get_field($s, 1, SQLSRV_PHPTYPE_STRING('utf-8'));
if ($utf8 !== false) {
    fatalError('sqlsrv_get_field should have failed with an error.');
}
print_r(sqlsrv_errors());

$drop_proc = "DROP PROCEDURE Utf16InvalidOut";
$s = sqlsrv_query($conn, $drop_proc);

$create_proc = <<<PROC
CREATE PROCEDURE Utf16InvalidOut
	@param nvarchar(25) OUTPUT
AS
BEGIN
    set @param = convert(nvarchar(25), 0x410042004300440000DCFFDB45004600);
END;
PROC;

$s = sqlsrv_query($conn, $create_proc);
if ($s === false) {
    die(print_r(sqlsrv_errors(), true));
}

$invalid_utf16_out = "";

$s = sqlsrv_query(
    $conn,
    "{call Utf16InvalidOut(?)}",
                   array( array( &$invalid_utf16_out, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING('utf-8'), SQLSRV_SQLTYPE_NVARCHAR(25)) )
);
if ($s !== false) {
    echo "invalid utf16:<$invalid_utf16_out>\n";
    fatalError('sqlsrv_query should have failed with an error');
}
print_r(sqlsrv_errors());

sqlsrv_query($conn, "DROP TABLE utf16invalid");
sqlsrv_query($conn, $drop_proc);

sqlsrv_close($conn);
echo "Test succeeded.\n";

?>
--EXPECTF--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -42
            [code] => -42
            [2] => An error occurred translating string for a field to UTF-8: %a
            [message] => An error occurred translating string for a field to UTF-8: %a
        )

)
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -41
            [code] => -41
            [2] => An error occurred translating string for an output param to UTF-8: %a
            [message] => An error occurred translating string for an output param to UTF-8: %a
        )

)
Test succeeded.
