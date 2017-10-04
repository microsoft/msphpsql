--TEST--
Prepare, execute statement and fetch with pooling enabled
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Allow PHP types for numeric fields
    $connection_options = array(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => TRUE);

    // Create a pool
    $conn0 = connect('ConnectionPooling=1', $connection_options);
    unset($conn0);

    // Connection can use an existing pool
    $conn = connect('ConnectionPooling=1', $connection_options);

    // Create table
    $tableName = 'pdo_061test';
    createTable($conn, $tableName, array("Столица" => "nvarchar(32)", "year" => "int"));

    // Insert data
    insertRow($conn, $tableName, array("Столица" => "Лондон", "year" => 2012), "prepareExecuteBind");

    // Get data
    $row = selectRow($conn, $tableName, "PDO::FETCH_ASSOC");
    var_dump($row);  
    unset($conn);

    // Create a new pool
    $conn0 = connect('ConnectionPooling=1');
    unset($conn0);
        
    // Connection can use an existing pool
    $conn = connect('ConnectionPooling=1');

    // Get data
    $row = selectRow($conn, $tableName, "PDO::FETCH_ASSOC");
    var_dump($row);

    // Close connection
    dropTable($conn, $tableName);
    unset($stmt);
    unset($conn);
    print "Done\n";
} catch (PDOException $e) {
    var_dump($e->errorInfo);
}
?>
--EXPECT--
array(2) {
  ["Столица"]=>
  string(12) "Лондон"
  ["year"]=>
  int(2012)
}
array(2) {
  ["Столица"]=>
  string(12) "Лондон"
  ["year"]=>
  string(4) "2012"
}
Done