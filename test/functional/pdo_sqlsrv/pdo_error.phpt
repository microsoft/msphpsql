--TEST--
Test the PDO::errorCode() and PDO::errorInfo() methods.
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    $db = connect();
    $tbname = "PDO_test_error";
    
    // create a dummy table
    createTable($db, $tbname, array(new ColumnMeta("int", "id")));
    
    try {
        // query with a wrong column name -- catch the exception and show errors
        $stmt = $db->query("SELECT * FROM $tbname WHERE IntColX = 1");
        echo "Should have thrown an exception!\n";
    } catch (PDOException $e) {
        echo $db->errorCode() . PHP_EOL;
        if ($e->getCode() != $db->errorCode()) {
            echo "Error codes do not match!\n";
            echo $e->getCode() . PHP_EOL;
        }
        $info = $db->errorInfo();
        print_r($info);
        if ($e->errorInfo != $info) {
            echo "Error info arrays do not match!\n";
            print_r($e->errorInfo);
        }
    }

    dropTable($db, $tbname);
    unset($db);
} catch (PDOException $e) {
    var_dump($e);
}
?>
--EXPECTREGEX--
42S22
Array
\(
    \[0\] => 42S22
    \[1\] => 207
    \[2\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Invalid column name 'IntColX'\.
    \[3\] => 42000
    \[4\] => 8180
    \[5\] => \[Microsoft\]\[ODBC Driver 1[1-9] for SQL Server\]\[SQL Server\]Statement\(s\) could not be prepared\.
\)