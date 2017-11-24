--TEST--
Test setting invalid encoding attributes
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    // valid option: should have no error
    @$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_DEFAULT);
    print_r(($conn->errorInfo())[2]);
    echo "\n";

    // PDO::SQLSRV_ENCODING_UTF8 should not be quoted
    @$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, "PDO::SQLSRV_ENCODING_UTF8");
    print_r(($conn->errorInfo())[2]);
    echo "\n";

    // PDO::SQLSRV_ENCODING_BINARY is not supported
    @$conn->setAttribute(PDO::SQLSRV_ATTR_ENCODING, PDO::SQLSRV_ENCODING_BINARY);
    print_r(($conn->errorInfo())[2]);
    echo "\n";
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>

--EXPECT--

An invalid encoding was specified for SQLSRV_ATTR_ENCODING.
An invalid encoding was specified for SQLSRV_ATTR_ENCODING.
