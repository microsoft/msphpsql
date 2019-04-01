--TEST--
Fix for output string parameter truncation error
--DESCRIPTION--
This test includes calling sqlsrv_query with an array of parameters with a named key, which should result in an error.
--SKIPIF--
<?php require('skipif_versions_old.inc'); ?>
--FILE--
<?php
require_once('MsCommon.inc');
$conn = AE\connect();

dropProc($conn, 'test_output');

$create_proc = <<<PROC
CREATE PROC [test_output] ( @p1 CHAR(512), @p2 VARCHAR(512) OUTPUT)
AS
BEGIN
	SELECT @p2 = CONVERT( VARCHAR(512), @p1 )
END
PROC;
$s = sqlsrv_query($conn, $create_proc);
if ($s === false) {
    die(print_r(sqlsrv_errors(), true));
}

$inValue1 = "Some data";
$outValue1 = "";
$tsql = '{CALL [test_output] (?, ?)}';

$s = sqlsrv_query(
    $conn,
    $tsql,
    array("k1" => array($inValue1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(512)),
                           array(&$outValue1, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(512)))
);

if ($s !== false) {
    echo "Expect this to fail!\n";
} else {
    print_r(sqlsrv_errors());
}

$s = sqlsrv_query(
    $conn,
    $tsql,
    array(array($inValue1, SQLSRV_PARAM_IN, null, SQLSRV_SQLTYPE_VARCHAR(512)),
                           array(&$outValue1, SQLSRV_PARAM_OUT, SQLSRV_PHPTYPE_STRING(SQLSRV_ENC_CHAR), SQLSRV_SQLTYPE_VARCHAR(512)))
);

if ($s === false) {
    die(print_r(sqlsrv_errors(), true));
}

print_r(strlen($outValue1));

echo "\n$outValue1";

dropProc($conn, 'test_output');

sqlsrv_free_stmt($s);
sqlsrv_close($conn);

?>
--EXPECT--
Array
(
    [0] => Array
        (
            [0] => IMSSP
            [SQLSTATE] => IMSSP
            [1] => -57
            [code] => -57
            [2] => String keys are not allowed in parameters arrays.
            [message] => String keys are not allowed in parameters arrays.
        )

)
512
Some data                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                                       
