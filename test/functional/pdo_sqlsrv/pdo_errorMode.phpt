--TEST--
Test different error modes. The queries will try to do a select on a table that does not exist on database.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

function testException()
{
    $db = connect();
    $sql = "SELECT * FROM temp_table";
    try {
        $q = $db->query($sql);
    } catch (Exception $e) {
        echo 'Caught exception: ', $e->getMessage();
    }
}

function testWarning()
{
    $db = connect("", array(), PDO::ERRMODE_WARNING);
    $sql = "SELECT * FROM temp_table";
    $q = $db->query($sql);
}

function testSilent()
{
    $db = connect("", array(), PDO::ERRMODE_SILENT);
    $sql = "SELECT * FROM temp_table";
    $q = $db->query($sql);
}

testException();
testWarning();
testSilent();
?>
--EXPECTREGEX--
Caught exception: SQLSTATE\[42S02\]: \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid object name 'temp_table'\.
Warning: PDO::query\(\): SQLSTATE\[42S02\]: Base table or view not found: 208 \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid object name 'temp_table'\. in .*pdo_errorMode\.php on line [0-9]+
