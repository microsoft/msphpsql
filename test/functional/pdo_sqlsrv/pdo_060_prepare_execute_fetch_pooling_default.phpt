--TEST--
Prepare, execute statement and fetch with pooling unset (default)
--SKIPIF--
<?php require('skipif_mid-refactor.inc'); ?>
--FILE--
<?php
require_once("MsCommon_mid-refactor.inc");

try {
    // Allow PHP types for numeric fields
    $connection_options = array(PDO::SQLSRV_ATTR_FETCHES_NUMERIC_TYPE => TRUE);

    // Create a pool
    $conn0 = connect('', $connection_options);
    unset($conn0);

    // Connection can use an existing pool
    $conn = connect('', $connection_options);
        
    // Create table
    $tableName = 'pdo_060_test';
    createTable($conn, $tableName, array("Столица" => "nvarchar(32)", "year" => "int"));

    // Insert data
    insertRow($conn, $tableName, array("Столица" => "Лондон", "year" => 2012), "prepareExecuteBind");

    // Get data
    $row = selectRow($conn, $tableName, "PDO::FETCH_ASSOC");
    var_dump($row);  
    unset($conn);

    // Create a new pool
    $conn0 = connect();
    unset($conn0);
        
    // Connection can use an existing pool
    $conn = connect();

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