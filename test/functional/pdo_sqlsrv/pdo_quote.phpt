--TEST--
Test the PDO::quote() method.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $conn = connect("", array(PDO::SQLSRV_ATTR_ENCODING => 3, PDO::ATTR_CASE => 2));

    $unquoted = "ABC'DE";
    $quoted1 = $conn->quote($unquoted);
    $quoted2 = $conn->quote($quoted1);

    var_dump($unquoted);
    var_dump($quoted1);
    var_dump($quoted2);
} catch (PDOException $e) {
    var_dump($e);
    exit;
}
?>

--EXPECT--
string(6) "ABC'DE"
string(9) "'ABC''DE'"
string(15) "'''ABC''''DE'''"
