--TEST--
Test PDO::prepare by passing in invalid scrollable type value
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(), PDO::ERRMODE_SILENT);

    // PDO::SQLSRV_CURSOR_BUFFERED should not be quoted
    $stmt1 = $conn->prepare("SELECT 1", array( PDO::ATTR_CURSOR => PDO::CURSOR_SCROLL, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => "PDO::SQLSRV_CURSOR_BUFFERED" ));

    // if ATTR_CURSOR is FWDONLY, cannot set SCROLL_TYPE
    $stmt2 = $conn->prepare("SELECT 2", array( PDO::ATTR_CURSOR => PDO::CURSOR_FWDONLY, PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE => PDO::SQLSRV_CURSOR_BUFFERED ));

    if ($stmt1 || $stmt2) {
        echo "Invalid values for PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE should return false.\n";
    } else {
        echo "Invalid values for PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE return false.\n";
    }
} catch (PDOException $e) {
    echo $e->getMessage();
}
?>

--EXPECT--
Invalid values for PDO::SQLSRV_ATTR_CURSOR_SCROLL_TYPE return false.
