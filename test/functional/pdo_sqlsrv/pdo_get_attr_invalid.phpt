--TEST--
Test getting invalid attributes
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php

require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    @$conn->getAttribute(PDO::ATTR_FETCH_TABLE_NAMES);
    print_r(($conn->errorInfo())[2]);
    echo "\n";

    @$conn->getAttribute(PDO::ATTR_CURSOR);
    print_r(($conn->errorInfo())[2]);
    echo "\n";

    @$conn->getAttribute(PDO::ATTR_CONNECTION_STATUS);
    print_r(($conn->errorInfo())[2]);
    echo "\n";
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>
--EXPECT--
An unsupported attribute was designated on the PDO object.
The given attribute is only supported on the PDOStatement object.
An invalid attribute was designated on the PDO object.
