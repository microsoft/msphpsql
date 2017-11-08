--TEST--
Test the PDO::errorCode() and PDO::errorInfo() methods.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");
require_once("MsData_PDO_AllTypes.inc");

try {
    $db = connect();
    $tbname = "PDO_MainTypes";
    createTableMainTypes($db, $tbname);
    // query with a wrong column name.
    $db->query("SELECT * FROM $tbname WHERE IntColX = 1");

    dropTable($db, $tbname);
    unset($conn);
} catch (PDOException $e) {
    print($db->errorCode());
    echo "\n";
    print_r($db->errorInfo());
}
?>
--EXPECTREGEX--
42S22
Array
\(
    \[0\] => 42S22
    \[1\] => 207
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid column name 'IntColX'\.
\)