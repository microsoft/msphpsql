--TEST--
Encoding of sqlsrv errors
--SKIPIF--
<?php require('skipif.inc'); ?>
--FILE--
<?php
header('content-type: text/plain;encoding=ISO-8859-1');

require_once("MsCommon.inc");

$conn = connect(array( 'CharacterSet'=>'UTF-8' ));
if (!$conn) {
    die(print_r(sqlsrv_errors(), true));
}

$stmt = sqlsrv_query($conn, "SET LANGUAGE German");
if (!$stmt) {
    print_r(sqlsrv_errors());
    exit;
}
sqlsrv_free_stmt($stmt);

$stmt = sqlsrv_query($conn, "select *, BadColumn from sys.syslanguages");
if ($stmt) {
    echo 'OK!';
    sqlsrv_free_stmt($stmt);
} else {
    $errs = sqlsrv_errors();
    print_r($errs);
}

sqlsrv_close($conn);

?>
--EXPECTF--
Array
(
    [0] => Array
        (
            [0] => 42S22
            [SQLSTATE] => 42S22
            [1] => 207
            [code] => 207
            [2] => %SUngültiger Spaltenname %cBadColumn%c.
            [message] => %SUngültiger Spaltenname %cBadColumn%c.
        )

)
