--TEST--
Test setting invalid value or key in connection attributes
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    // Negative value for query timeout: should raise error
    @$conn->setAttribute(PDO::SQLSRV_ATTR_QUERY_TIMEOUT, -1);
    print_r(($conn->errorInfo())[2]);
    echo "\n";

    // PDO::ATTR_CURSOR is a Statement Level Attribute only
    @$conn->setAttribute(PDO::ATTR_CURSOR, PDO::CURSOR_SCROLL);
    print_r(($conn->errorInfo())[2]);
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>

--EXPECT--
Invalid value -1 specified for option PDO::SQLSRV_ATTR_QUERY_TIMEOUT.
The given attribute is only supported on the PDOStatement object.
