--TEST--
Test PDO::prepare by passing in invalid cursor value
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    // PDO::CURSOR_FWDONLY should not be quoted
    $stmt1 = $conn->prepare("SELECT 1", array( PDO::ATTR_CURSOR => "PDO::CURSOR_FWDONLY" ));
    print_r(($conn->errorInfo())[2]);
    echo "\n";

    // 10 is an invalid value for PDO::ATTR_CURSOR
    $stmt2 = $conn->prepare("SELECT 2", array( PDO::ATTR_CURSOR => 10 ));
    print_r(($conn->errorInfo())[2]);
    echo "\n";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>

--EXPECT--
An invalid cursor type was specified for either PDO::ATTR_CURSOR or PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE
An invalid cursor type was specified for either PDO::ATTR_CURSOR or PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE
